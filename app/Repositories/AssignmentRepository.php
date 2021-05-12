<?php

namespace App\Repositories;

use App\Agent;
use App\Assignment;
use App\Services\Zendesk\Ticket;
use Illuminate\Support\Facades\DB;
use App\Collections\AgentCollection;
use App\Collections\AssignmentCollection;
use App\Services\Assignments\PreparedAssignmentCollection;
use App\Services\Assignments\RoundRobinEngine;

class AssignmentRepository
{
    protected $agentRepository;

    protected $roundRobinEngine;

    public function __construct(AgentRepository $agentRepository, RoundRobinEngine $roundRobinEngine)
    {
        $this->agentRepository = $agentRepository;
        $this->roundRobinEngine = $roundRobinEngine;
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
                'type' => Agent::OBSERVED_UNASSIGNMENT,
                'batch' => $batch,
                'created_at' => now()
            ];
        });

        return $unassigments;
    }

    public function getRecentlyFailedAssignments(): AssignmentCollection
    {
        return Assignment::where('response_status', Assignment::RESPONSE_STATUS_FAILED)->where('type', Assignment::TYPE_ASSIGNMENT)->where('created_at', '>', now()->subMinutes(10))->get();
    }

    public function getLatestAssignmentsPerView(AgentCollection $agents): AssignmentCollection
    {
        return Assignment::whereIn('agent_id', $agents->map->id->all())
                        ->where('type', '!=', Agent::RETRIED_ASSIGNMENT)
                        ->select(DB::raw('MAX(id) as id'), "agent_id", "zendesk_view_id")
                        ->groupBy(['agent_id', 'zendesk_view_id'])
                        ->get();
    }

    public function createLogs(PreparedAssignmentCollection $preparedAssignments): void
    {
        $preparedAssignments->toAssignments()
                            ->whenNotEmpty(function(AssignmentCollection $assignments) {
                                return Assignment::insert($assignments->map->toArray()->all());
                            });
    }

    public function updateLogs($batch, array $successTicketIds, array $failedTicketIds): void
    {
        $builder = Assignment::where('batch_id', $batch);
        if (count($successTicketIds) > 0) {
            $builder->whereIn('zendesk_ticket_id', $successTicketIds)->update(['response_status' => Assignment::RESPONSE_STATUS_SUCCESS]);
        }

        if (count($failedTicketIds) > 0) {
            $builder->whereIn('zendesk_ticket_id', $failedTicketIds)->update(['response_status' => Assignment::RESPONSE_STATUS_FAILED]);
        }  
    }
}