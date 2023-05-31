<?php

namespace App\Collections;

use App\Models\Agent;
use Exception;
use App\Models\Assignment;
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

    public function reconcile($successTicketIds, $failedResultDetails = [], $jobId = '', $jobMessage = '') {
        return $this->updateStatus($successTicketIds, $failedResultDetails, $jobId, $jobMessage);
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
        return $this->mapToGroups(fn($a) => [sprintf('%s-%s-%s-%s', $a->batch, $a->status, optional($a)->error, optional($a)->details) => $a])
            ->map(function($assignments) {
                $ticket_ids = $assignments->pluck('ticket_id')->values()->all();
                $batch = $assignments->first()->batch;
                $status = $assignments->first()->status;
                $error = optional($assignments->first())->error;
                $details = optional($assignments->first())->details;
                $zendesk_job_id = optional($assignments->first())->zendesk_job_id;
                $zendesk_job_message = optional($assignments->first())->zendesk_job_message;

                $assignmentBuilder = Assignment::where('batch_id', $batch)
                                                ->whereIn('zendesk_ticket_id', $ticket_ids);
                
                return $assignmentBuilder->update([
                    'response_status' => $status,
                    'zendesk_job_id' => $zendesk_job_id,
                    'zendesk_job_message' => $zendesk_job_message,
                    'response_error' => $error,
                    'response_details' => $details
                ]);
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
                "zendesk_ticket_created_at" => $assignment->ticket_created_at,
                "zendesk_ticket_updated_at" => $assignment->ticket_updated_at,
                "zendesk_ticket_status" => $assignment->ticket_status,
                "zendesk_ticket_requester_id" => $assignment->ticket_requester_id,
                "assigned_at" => $assignment->assigned_at,
                "response_status" => $assignment->status ?? "PENDING",
                "created_at" => $assignment->created_at
            ];
        })->all();
    }

    private function updateStatus($successTicketIds, $failedResultDetails = [], $jobId = '', $jobMessage = '') {
        $failedTicketIds = collect($failedResultDetails)->pluck('id')->all();
        $resultsDict = collect($failedResultDetails)->mapWithKeys(fn($result) => [$result->id => (array) $result]);
        
        $processAssignments = $this->whereIn('ticket_id', array_merge($successTicketIds, $failedTicketIds))->map(function($assignment) use ($successTicketIds, $failedTicketIds, $resultsDict, $jobId, $jobMessage){
            if (in_array($assignment->ticket_id, $failedTicketIds)) {
                $assignment->status = "FAILED";
                $assignment->error = isset($resultsDict[$assignment->ticket_id]['error']) ? $resultsDict[$assignment->ticket_id]['error'] : null;
                $assignment->details = isset($resultsDict[$assignment->ticket_id]['details']) ? $resultsDict[$assignment->ticket_id]['details'] : null;
            } else if (in_array($assignment->ticket_id, $successTicketIds)) {
                $assignment->status = 200;
            }
            $assignment->zendesk_job_id = $jobId; 
            $assignment->zendesk_job_message = $jobMessage; 

            return $assignment;
        });

        return $processAssignments;
    }
}