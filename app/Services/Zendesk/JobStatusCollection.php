<?php

namespace App\Services\Zendesk;

use Illuminate\Support\Collection;
use App\Collections\BatchableCollection;
use App\Services\ZendeskService;

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

    public function fresh() {
        $this->newlyCompletedIds = [];

        $updatedJobStatuses = app(ZendeskService::class)->getJobStatuses($this->ids()->all());
        
        $updatedJobStatuses->each(function($jobStatus) {
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


}