<?php

namespace App\Repositories;

use App\Models\Agent;
use App\Models\Assignment;
use \DB;
use App\Traits\RoundRobinable;
use App\Services\Zendesk\Ticket;
use Illuminate\Support\Collection;
use App\Collections\AgentCollection;
use Illuminate\Support\Facades\Cache;
use App\Repositories\Traits\Batchable;
use App\Collections\AssignmentCollection;
use App\Services\Zendesk\TicketCollection;
use Zendesk\API\Resources\Core\TicketComments;

class AssignmentRepository
{
    use RoundRobinable, Batchable;

    protected $cachePrefix = "assignments";

    protected $ticketRepository;

    public function __construct(TicketRepository $ticketRepository)
    {
        $this->ticketRepository = $ticketRepository;
    }

    public function retrieveAssignments($batch) {
        return new AssignmentCollection($this->cache($batch)->all());
    }

    public function makeAssignments($batch, Collection $tasks) {
        $tickets = $tasks->map(function($task) {
            return $this->ticketRepository->getAssignableByView($task->zendesk_view_id)->each(function($ticket) use ($task) {
                $ticket->view_id = $task->zendesk_view_id;
                $ticket->assigned_at = now();
            });
        })->flatten();
        $previousFailedAssignments = Assignment::where('response_status', 'FAILED')->where('type', 'ASSIGNMENT')->where('created_at', '>', now()->subMinutes(10))->get(); // TODO: tes jika agent offline (reassign) terus online lagi

        $ticketsByView = $tickets->groupBy(function($ticket) {
            return $ticket->view_id;
        });

        return $ticketsByView->reduce(function($assignments, $tickets, $view_id) use ($tasks, $batch, $previousFailedAssignments) {
            $assignedTicketIds = $assignments->isNotEmpty() ? $assignments->pluck('ticket_id')->all() : [];

            $task = $tasks->firstWhere('zendesk_view_id', $view_id);
            $agents = $task->getCustomStatusAvailableAgents();
            $tickets = new TicketCollection($tickets->reject(fn($ticket) => in_array($ticket->id, $assignedTicketIds))->values()->all());
            $filteredPreviousFailedAssignments = collect($previousFailedAssignments->whereNotIn('zendesk_ticket_id', $assignedTicketIds)->all());

            return $assignments->merge($this->createAssignments($agents, $tickets, $batch, $filteredPreviousFailedAssignments, $view_id));
        }, new AssignmentCollection())->flatten();
    }

    public function makeUnassignments($batch, AgentCollection $agents) {
        $tickets = $this->ticketRepository->getAssignedByAgents($agents);

        $agentDictionary = $agents->groupById();
        $unassignments = $tickets->map(function($ticket) use ($agentDictionary, $batch) {
            $agent = $agentDictionary->getByTicket($ticket);
            
            if (!$agent) {
                return;
            }

            return (object) [
                'agent_id' => $agent->id,
                'agent_fullName' => $agent->fullName,
                "agent_zendesk_agent_id" => $agent->zendesk_agent_id,
                "agent_zendesk_group_id" => $agent->zendesk_group_id,
                'agent_zendesk_custom_field_id' => $agent->zendesk_custom_field_id,
                'ticket_id' => $ticket->id,
                'ticket_subject' => $ticket->subject,
                'ticket_created_at' => $ticket->created_at,
                'ticket_updated_at' => $ticket->updated_at,
                'ticket_status' => $ticket->status,
                'ticket_requester_id' => $ticket->requester_id,
                'ticket_from_messaging_channel' => $ticket->from_messaging_channel,
                'ticket_via_channel' => optional($ticket->via)->channel,
                'assigned_at' => now(),
                'type' => Agent::UNASSIGNMENT,
                'batch' => $batch,
                'created_at' => now()
            ];
        })->reject(function($ticket) {
            return !$ticket;
        });
        
        return $unassignments;
    }

    public function makeObservedUnassignments($batch) {
        $observedTickets = $this->ticketRepository->getObserved();
        $agents = Agent::disableCache()->where('status', true);

        $unassignableTickets = $observedTickets->reject(function(Ticket $ticket) use ($agents) {
            return $agents->where('zendesk_agent_id', $ticket->assignee_id)->where('zendesk_group_id', $ticket->group_id)->where('zendesk_custom_field_id', $ticket->customFieldValue())->first();
        });
        
        if ($unassignableTickets->isEmpty()) {
            return collect();
        }

        $agentNames = $unassignableTickets->map(function(Ticket $ticket) {
            return $ticket->customFieldValue();
        })->all();

        $agents = Agent::disableCache()->whereIn('zendesk_custom_field_id', $agentNames)->get();
          
        $agentDictionary = $agents->groupById();
        $unassigments = $unassignableTickets->map(function(Ticket $ticket) use ($batch, $agentDictionary) {
            $agent = $agentDictionary->getByTicket($ticket);
            return (object) [
                'agent_id' => $agent->id,
                'agent_fullName' => $agent->fullName,
                "agent_zendesk_agent_id" => $agent->zendesk_agent_id,
                "agent_zendesk_group_id" => $agent->zendesk_group_id,
                'agent_zendesk_custom_field_id' => $agent->zendesk_custom_field_id,
                'ticket_id' => $ticket->id,
                'ticket_subject' => $ticket->subject,
                'ticket_created_at' => $ticket->created_at,
                'ticket_updated_at' => $ticket->updated_at,
                'ticket_status' => $ticket->status,
                'assigned_at' => now(),
                'type' => Agent::OBSERVED_UNASSIGNMENT,
                'batch' => $batch,
                'created_at' => now()
            ];
        });

        return $unassigments;
    }

    public function prepareAssignment($batch, Collection $tasks) {
        $assignments = $this->makeAssignments($batch, $tasks);

        return new AssignmentCollection($this->cache($batch, $assignments)->all());
    }
    
    public function prepareUnassignment($batch, AgentCollection $agents) {
        $unassigments = $this->makeUnassignments($batch, $agents);
        
        return new AssignmentCollection($this->cache($batch, $unassigments)->all());
    }

    public function getTotalAssignmentsByDateRange() {
        $total_today =  Assignment::where('response_status', '200')->whereDate('assigned_at', now()->today())->count();
        $assignment_counts = Cache::remember('assignments_counts', now()->endOfDay(), function() {
            return [
                'total_yesterday' => Assignment::where('response_status', '200')->whereDate('assigned_at', now()->yesterday())->count(),
                'total_this_week_to_yesterday' => Assignment::where('response_status', '200')->whereBetween('assigned_at', [now()->startOfWeek(), now()->yesterday()->endOfDay()])->count(),
                'total_this_month_to_yesterday' => Assignment::where('response_status', '200')->whereBetween('assigned_at', [now()->startOfMonth(), now()->yesterday()->endOfDay()])->count()
            ];
        });

        return [
            'today' => $total_today,
            'yesterday' => $assignment_counts['total_yesterday'],
            'this_week' => $assignment_counts['total_this_week_to_yesterday'] + $total_today,
            'this_month' => $assignment_counts['total_this_month_to_yesterday'] + $total_today,
        ];
    }

    public function getTotalFailedAssignmentsByDateRange() {
        $today = now()->today();
        $failedToday = Assignment::where('response_status', 'FAILED')->whereDate('created_at', $today)->count();
        return [
            'today' => $failedToday
        ];
    }

}