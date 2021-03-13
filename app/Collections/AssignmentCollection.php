<?php

namespace App\Collections;

use App\Agent;
use Exception;
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
                    "body" => $assignment->type == Agent::OBSERVED_UNASSIGNMENT ? "BAGIRATA Observed Unassignment: " . $assignment->agent_fullName : "BAGIRATA Agent Unavailable: " . $assignment->agent_fullName,
                    "author_id" => $assignment->agent_zendesk_agent_id,
                    "public" => false
                ]
                ];
        });
    }

    public function reconcile($successTicketIds, $failedTicketIds) {
        return $this->updateStatus($successTicketIds, $failedTicketIds);
    }

    public function ticketIds() {
        return $this->pluck('ticket_id')->values();
    }

    public function onlyAssignment() {
        return $this->where('type', Agent::ASSIGNMENT);
    }

    public function onlyUnassignment() {
        return $this->where('type', Agent::ASSIGNMENT);
    }

    public function success() {
        return $this->where('status', 200);
    }

    public function update() {
        $jobStatuses = $this->chunk(100)->map(function($assignments) {
            return app(TicketRepository::class)->assign($assignments->values()->assignmentParams());
        })->flatten();

        return new JobStatusCollection($jobStatuses->values()->all());
    }

    public function updateUnassignment() {
        $jobStatuses = $this->chunk(100)->filterMap(function($assignments) {
            try {
                return app(TicketRepository::class)->assign($assignments->values()->unassignmentParams());
            } catch (ApiResponseException $e) {
                logs()->error($e);
            }
        })->flatten();

        return new JobStatusCollection($jobStatuses->values()->all());
    }

    public function createLogs() {
        if ($this->isNotEmpty()) {            
            return Assignment::insert($this->toLogParams());
        }
        return;
    }

    public function updateLogs() {
        return $this->groupBy(['batch', 'status'])->map(function($assignmentsByStatus, $batch) {

            return $assignmentsByStatus->map(function($assignments, $status) use ($batch) {
                $assignmentBuilder = Assignment::where('batch_id', $batch)->whereIn('zendesk_ticket_id', $assignments->pluck('ticket_id')->values()->all());
                return $assignmentBuilder->update(['response_status' => $status]);
            });

        });        
    }

    private function toLogParams() {
        return $this->map(function($assignment, $i) {
            return [
                "type" => $assignment->type,
                "batch_id" => $assignment->batch,
                "agent_id" => $assignment->agent_id,
                "agent_name" => $assignment->agent_fullName,
                "zendesk_view_id" => $assignment->view_id ?? "viewId",
                "zendesk_ticket_id" => $assignment->ticket_id,
                "zendesk_ticket_subject" => $assignment->ticket_subject,
                "response_status" => $assignment->status ?? "PENDING",
                "created_at" => $assignment->created_at
            ];
        })->all();
    }

    private function updateStatus($successTicketIds, $failedTicketIds) {
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
}