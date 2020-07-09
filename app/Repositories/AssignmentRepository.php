<?php

namespace App\Repositories;

use App\Agent;
use App\Assignment;
use App\Collections\AgentCollection;
use App\Traits\RoundRobinable;
use Illuminate\Support\Collection;
use App\Collections\TicketCollection;
use Illuminate\Support\Facades\Cache;
use App\Repositories\Traits\Batchable;
use App\Collections\AssignmentCollection;

class AssignmentRepository
{
    use RoundRobinable, Batchable;

    protected $cachePrefix = "assignments";

    protected $ticketRepository;

    public function __construct(TicketRepository $ticketRepository)
    {
        $this->ticketRepository = $ticketRepository;
    }

    public function prepare($batch, $agents, $tickets) {
        $batchedAssignments = $this->createAssignments($agents, $tickets)->map(function($assignment) use ($batch) {
            $assignment->type = Agent::ASSIGNMENT;
            $assignment->batch = $batch;
            $assignment->status = "PENDING";
            return $assignment;
        });

        return new AssignmentCollection($this->cache($batch, $batchedAssignments->values())->all());
    }

    public function getPrepared($batch) {
        return new AssignmentCollection($this->cache($batch)->all());
    }

    public function makeAssignments(Collection $tasks) {
        return (new AssignmentCollection($tasks->all()))->map(function($task) {
            $agents = $task->getAvailableAgents();
            $tickets = $this->ticketRepository->getAssignableByView($task->zendesk_view_id);
            
            return $this->createAssignments($agents, $tickets);
        })->flatten();        
    }

    public function prepareAssignment($batch, Collection $tasks) {
        $assignments = $this->makeAssignments($tasks);

        $batchedAssignments = $assignments->map(function($assignment) use ($batch) {
            $assignment->type = Agent::ASSIGNMENT;
            $assignment->batch = $batch;
            $assignment->status = "PENDING";
            return $assignment;
        });

        return $this->cache($batch, $batchedAssignments->values());
    }
    
    public function prepareUnassignment($batch, Collection $agents) {
        $tickets = $this->ticketRepository->getAssigned($agents);

        $batchedUnassignments = $tickets->map(function($ticket) use ($batch, $agents) {
            $agent = $agents->getByTicket($ticket);
            return (object) [
                'agent_id' => $agent->id,
                'agent_fullName' => $agent->fullName,
                "agent_zendesk_agent_id" => $agent->zendesk_agent_id,
                "agent_zendesk_group_id" => $agent->zendesk_group_id,
                'agent_zendesk_custom_field_id' => $agent->zendesk_custom_field_id,
                'ticket_id' => $ticket->id,
                'ticket_subject' => $ticket->subject,
                'batch' => $batch,
                'type' => Agent::UNASSIGNMENT,
                'status' => "PENDING"
            ];
        });

        return new AssignmentCollection($this->cache($batch, $batchedUnassignments->values())->all());
    }
}