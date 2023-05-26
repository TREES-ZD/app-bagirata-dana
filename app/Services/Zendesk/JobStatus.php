<?php

namespace App\Services\Zendesk;

class JobStatus
{
    public $jobStatus;

    public function __construct($jobStatus)
    {
        $this->jobStatus = $jobStatus;
    }

    public function completed() {
        return $this->status == "completed";
    }

    public function id() {
        return $this->id;
    }

    public function successTicketIds() {
        return collect($this->results)->filter(function($ticket) {
            return optional($ticket)->status;
            }
        )->pluck('id');
    }

    public function failedTicketIds() {
        return collect($this->results)->reject(function($ticket) {
            return optional($ticket)->status;
            }
        )->pluck('id');
    }

    public function failedResultDetails() {
        return collect($this->results)
                ->filter(fn($result) => !optional($result)->status);
    }

    public function __get($name)
    {
        if (isset($this->jobStatus->$name)) {
            return $this->jobStatus->$name;
        } else {
            return null;
        };
    }
}