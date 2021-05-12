<?php

namespace App\Services\Zendesk;

use App\Services\ZendeskService;
use Illuminate\Support\Collection;
use App\Services\Zendesk\ZendeskWrapper;

class JobStatusCollection extends Collection
{
    protected $newlyCompletedIds = [];

    protected $completedIds = [];

    public function ids() {
        return $this->pluck('jobStatus.id');
    }

    public function areAllCompleted() {
        return $this->every->completed();
    }

    public function refresh() {
        $this->newlyCompletedIds = [];

        $updatedJobStatuses = $this->updateJobStatus();

        $updatedJobStatuses->each(function(JobStatus $jobStatus) {
            if ($jobStatus->completed() && !$this->isAlreadyCompleted($jobStatus)) {
                $this->newlyCompletedIds[] = $jobStatus->id;
                $this->completedIds[] = $jobStatus->id;
            }
        });

        unset($this->items);
        $this->items = $updatedJobStatuses->all();

        return $this;
    }

    public function newlyCompleted() {
        if ($this->areAllCompleted()) {
            return $this;
        }

        return $this->filter(function($jobStatus) {
            return in_array($jobStatus->id, $this->newlyCompletedIds);
        });
    }

    private function isAlreadyCompleted($jobStatus) {
        return in_array($jobStatus->id, $this->completedIds);
    }

    private function updateJobStatus(): JobStatusCollection
    {
        /** @var \App\Services\Zendesk\ZendeskWrapper $zendesk */
        $zendesk = app(ZendeskWrapper::class);
        $response = $zendesk->showManyJobStatuses($this->ids()->all());

        return (new JobStatusCollection($response->job_statuses))->map(function($jobStatus) {
            return new JobStatus($jobStatus);
        });
    }

}