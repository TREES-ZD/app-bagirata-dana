<?php

namespace App\Collections;

use App\Agent;
use App\Assignment;
use Illuminate\Support\Collection;

class AssignmentCollection extends Collection
{
    protected $name = "assignments";

    public function toTickets($agentNameFieldId) {
        return $this->map(function($assignment) use ($agentNameFieldId) {                
            return [
                "id" => $assignment->ticket_id,
                "assignee_id" => $assignment->agent_zendesk_agent_id,
                "group_id" => $assignment->agent_zendesk_group_id,
                "custom_fields" => [
                    [
                    "id" => $agentNameFieldId,
                    "value" => $assignment->agent_zendesk_custom_field_id
                    ]
                ]
            ];
        })->values();

    }

    public function reconcileAssignment(TicketCollection $updatedTickets) {
        
        return $this->map(function($assignment) use ($updatedTickets) {
            $ticket = $updatedTickets->firstWhere('id', $assignment->ticket_id);

            if (!$ticket) return $assignment;

            $assignment->type = Agent::ASSIGNMENT;

            $customField = collect($ticket->custom_fields)->groupBy("id");
            $agentName = $customField->get(env("ZENDESK_AGENT_NAMES_FIELD", 360000282796))->first();

            if ($ticket->assignee_id == $assignment->agent_zendesk_agent_id && $ticket->group_id == $assignment->agent_zendesk_group_id && optional($agentName)->value == $assignment->agent_zendesk_custom_field_id) {
                $assignment->status = 200;
            } else {
                $assignment->status = "FAILED";
            }

            return $assignment;
        });
    }

    public function reconcileUnassignment(TicketCollection $updatedTickets) {
        return $this->map(function($assignment) use ($updatedTickets) {
            $ticket = $updatedTickets->firstWhere('id', $assignment->ticket_id);

            if (!$ticket) return $assignment;

            $customField = collect($ticket->custom_fields)->groupBy("id");
            $agentName = $customField->get(env("ZENDESK_AGENT_NAMES_FIELD", 360000282796))->first();
            
            if (!$ticket->assignee_id && !optional($agentName)->value) {
                $assignment->status = 200;
            } else {
                $assignment->status = "FAILED";
            }

            return $assignment;
        });
    }

    public function ticketIds() {
        return $this->pluck('ticket_id')->values();
    }

    public function logs() {
        $assignments = $this->map(function($assignment, $i) {
            return [
                "type" => $assignment->type,
                "batch_id" => $assignment->batch,
                "agent_id" => $assignment->agent_id,
                "agent_name" => $assignment->agent_fullName,
                "zendesk_view_id" => "viewId",
                "zendesk_ticket_id" => $assignment->ticket_id,
                "zendesk_ticket_subject" => $assignment->ticket_subject,
                "response_status" => $assignment->status,
                "created_at" => $assignment->type == Agent::ASSIGNMENT ? now()->addSeconds($i) : now()
            ];
        });
        Assignment::insert($assignments->all());
    }
}