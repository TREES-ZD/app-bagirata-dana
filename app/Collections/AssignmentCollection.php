<?php

namespace App\Collections;

use App\Agent;
use App\Assignment;
use Illuminate\Support\Collection;
use App\Repositories\TicketRepository;
use App\Services\Zendesk\JobCollection;
use App\Services\Zendesk\TicketCollection;
use App\Services\Zendesk\JobStatusCollection;
use Zendesk\API\Exceptions\ApiResponseException;

class AssignmentCollection extends Collection
{
    protected $name = "assignments";

    public function assignmentParams() {
        return $this->map(function($assignment) {                
            return [
                "id" => $assignment->ticket_id,
                "assignee_id" => $assignment->agent_zendesk_agent_id,
                "group_id" => $assignment->agent_zendesk_group_id,
                "custom_fields" => [
                    [
                    "id" => env("ZENDESK_AGENT_NAMES_FIELD", 360000282796),
                    "value" => $assignment->agent_zendesk_custom_field_id
                    ]
                ]
            ];
        });
    }

    public function unassignmentParams() {
        return $this->map(function($assignment) {                
            return [
                "id" => $assignment->ticket_id,
                "custom_fields" => [
                    [
                    "id" => env("ZENDESK_AGENT_NAMES_FIELD", 360000282796),
                    "value" => null
                    ]
                ],
                "comment" =>  [
                    "body" => "BAGIRATA Agent Unavailable: " . $assignment->agent_fullName,
                    "author_id" => $assignment->agent_zendesk_agent_id,
                    "public" => false
                ]
                ];
        });
    }

    public function reconcile($successTicketIds, $failedTicketIds) {
        
        $processAssignments = $this->whereIn('ticket_id', array_merge($successTicketIds, $failedTicketIds))->map(function($assignment) use ($successTicketIds, $failedTicketIds){

            if (in_array($assignment->ticket_id, $failedTicketIds)) {
                $assignment->status = "FAILED";
            } else if (in_array($assignment->ticket_id, $successTicketIds)) {
                $assignment->status = 200;
            }

            return $assignment;
        });

        return $processAssignments;
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

    public function success() {
        return $this->where('status', 200);
    }

    public function onlyAssignment() {
        return $this->filter(function($assignment) {
            return $assignment->type == Agent::ASSIGNMENT;
        });
    }

    public function onlyUnassignment() {
        return $this->filter(function($assignment) {
            return $assignment->type == Agent::UNASSIGNMENT;
        });
    }

    public function update() {
        $jobStatuses = $this->chunk(100)->map(function($assignments) {
            return app(TicketRepository::class)->assign($assignments->values()->assignmentParams());
        })->flatten();

        return new JobStatusCollection($jobStatuses->values()->all());
    }

    public function updateUnassignment() {
        $jobStatuses = $this->chunk(100)->map(function($assignments) {
            try {
                return app(TicketRepository::class)->assign($assignments->values()->unassignmentParams());
            } catch (ApiResponseException $e) {
                logs()->error($e);
            }
        })->flatten();

        return new JobStatusCollection($jobStatuses->values()->all());
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