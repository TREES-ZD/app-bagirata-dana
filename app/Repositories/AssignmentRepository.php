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
            });
        })->flatten();
        $previousFailedAssignments = Assignment::where('response_status', 'FAILED')->where('type', 'ASSIGNMENT')->where('created_at', '>', now()->subMinutes(10))->get(); // TODO: tes jika agent offline (reassign) terus online lagi

        // make unique tickets in multiple views
        $tickets = $tickets->unique(function($ticket) {
            return $ticket->id;
        });

        $ticketsByView = $tickets->groupBy(function($ticket) {
            return $ticket->view_id;
        });

        return (new AssignmentCollection($ticketsByView->all()))->map(function($tickets, $view_id) use ($tasks, $batch, $previousFailedAssignments) {
            $task = $tasks->firstWhere('zendesk_view_id', $view_id);
            $agents = $task->getCustomStatusAvailableAgents();
            $tickets = new TicketCollection($tickets->values()->all());

            return $this->createAssignments($agents, $tickets, $batch, collect($previousFailedAssignments->all()), $view_id);
        })->flatten();
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
        $today = now()->today();
        $yesterday = now()->yesterday();
        $firstDateLastWeek = now()->startOfWeek();
        $lastDateLastWeek = now()->endOfWeek();
        $firstDateLastMonth = now()->startOfMonth();
        $lastDateLastMonth = now()->endOfmonth();

        // $total = Assignment::selectRaw("COUNT(*) AS total, 
        //                                         CASE 
        //                                             WHEN DATE(created_at) = '{$today}' THEN 'today'
        //                                             WHEN DATE(created_at) = '{$yesterday}' THEN 'yesterday'
        //                                             WHEN DATE(created_at) BETWEEN '{$startDateLastWeek}' AND '{$endDateLastWeek}' THEN 'in_a_week'
        //                                             WHEN DATE(created_at) BETWEEN '{$startDateLastMonth}' AND '{$endDateLastMonth}' THEN 'in_a_month'
        //                                         END AS period")
        //     ->where('response_status', '200')
        //     ->whereIn(\DB::raw("DATE(created_at)"), [$today, $yesterday])
        //     ->orWhereBetween(\DB::raw("DATE(created_at)"), [$startDateLastWeek, $endDateLastWeek])
        //     ->orWhereBetween(\DB::raw("DATE(created_at)"), [$startDateLastMonth, $endDateLastMonth])
        //     ->groupBy('period')
        //     ->pluck('total', 'period')
        //     ->toArray();

        // $assignmentQuery = Assignment::where('response_status', '200');
        $totalToday =  Assignment::where('response_status', '200')->whereDate('created_at', $today)->count();
        $totalYesterday =  Assignment::where('response_status', '200')->whereDate('created_at', $yesterday)->count();
        $totalFailedInAWeek =  Assignment::where('response_status', '200')->whereBetween('created_at', [$firstDateLastWeek, $lastDateLastWeek])->count();
        $totalFailedInAMonth =  Assignment::where('response_status', '200')->whereBetween('created_at', [$firstDateLastMonth, $lastDateLastMonth])->count();
        return [
            'today' => $totalToday,
            'yesterday' => $totalYesterday,
            'in_a_week' => $totalFailedInAWeek,
            'in_a_month' => $totalFailedInAMonth,
        ];
    }

    public function getTotalFailedAssignmentsByDateRange() {
        $today = now()->today();
        $yesterday = now()->yesterday();
        $firstDateLastWeek = now()->subWeek()->startOfWeek();
        $lastDateLastWeek = now()->subWeek()->endOfWeek();
        $firstDateLastMonth = now()->subMonth()->startOfMonth();
        $lastDateLastMonth = now()->subMonth()->endOfmonth();
        
        // $failedAssignmentCounts = Assignment::query()
        //     ->selectRaw("COUNT(*) AS total")
        //     ->selectRaw("CONCAT('failed_', CASE 
        //                                       WHEN DATE(created_at) = '{$today}' THEN 'today'
        //                                       WHEN DATE(created_at) = '{$yesterday}' THEN 'yesterday'
        //                                       WHEN DATE(created_at) BETWEEN '{$startDateLastWeek}' AND '{$endDateLastWeek}' THEN 'in_a_week'
        //                                       WHEN DATE(created_at) BETWEEN '{$startDateLastMonth}' AND '{$endDateLastMonth}' THEN 'in_a_month'
        //                                   END) AS period")
        //     ->where('response_code', 'FAILED')
        //     ->whereIn(\DB::raw("DATE(created_at)"), [$today, $yesterday])
        //     ->orWhereBetween(\DB::raw("DATE(created_at)"), [$startDateLastWeek, $endDateLastWeek])
        //     ->orWhereBetween(\DB::raw("DATE(created_at)"), [$startDateLastMonth, $endDateLastMonth])
        //     ->groupBy('period')
        //     ->pluck('total', 'period')
        //     ->toArray();
        
        // $failedAssignmentCounts = array_combine(
        //     array_map(function ($key) {
        //         return 'failed_' . $key;
        //     }, array_keys($failedAssignmentCounts)),
        //     $failedAssignmentCounts
        // );

        $failedToday = Assignment::where('response_status', 'FAILED')->whereDate('created_at', $today)->count();
        $failedYesterday = Assignment::where('response_status', 'FAILED')->whereDate('created_at', $yesterday)->count();
        $failedInAWeek = Assignment::where('response_status', 'FAILED')->whereBetween('created_at', [$firstDateLastWeek, $lastDateLastWeek])->count();
        $failedInAMonth = Assignment::where('response_status', 'FAILED')->whereBetween('created_at', [$firstDateLastMonth, $lastDateLastMonth])->count();

        return [
            'today' => $failedToday,
            'yesterday' => $failedYesterday,
            'in_a_week' => $failedInAWeek,
            'in_a_month' => $failedInAMonth,
        ];
    }

}