<?php

namespace App\Services\Assignments;

use App\Assignment;
use Illuminate\Support\Collection;
use App\Repositories\Traits\Batchable;
use App\Collections\AssignmentCollection;

class PreparedAssignmentCollection extends Collection
{
    protected $cachePrefix = "assignments";
    
    public function toAssignments(): AssignmentCollection
    {
        return (new AssignmentCollection($this->all()))->map->toAssignment();
    }

    /** @todo tambah logic bedain antara assignment dan unassignment */
    public function toBody(): array
    {
        return $this->filterMap(function(PreparedAssignment $assignment) {
                            return $assignment->toBody();
                        })
                    ->values()
                    ->all();
    }

    public function createLogs(): void
    {
        $this->toAssignments()
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