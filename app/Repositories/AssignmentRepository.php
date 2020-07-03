<?php

namespace App\Repositories;

use App\Agent;
use App\Assignment;
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

    
    public function prepareUnassignment($batch, Agent $agent, Collection $tickets) {
        $batchedUnassignments = $tickets->map(function($ticket) use ($batch, $agent) {
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
     
    public function buildAssignments($batch, TicketCollection $tickets) {
        return $tickets->all();
    }
}