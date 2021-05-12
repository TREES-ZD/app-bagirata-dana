<?php

namespace App\Services\Zendesk;

class JobStatusService
{
    /** @var \App\Services\Zendesk\ZendeskWrapper $wrapper */
    protected $wrapper;

    public function __construct(ZendeskWrapper $wrapper)
    {
        $this->wrapper = $wrapper;
    }

    public function check($ids): JobStatusCollection
    {
        $response = $this->wrapper->showManyJobStatuses($ids);

        return (new JobStatusCollection($response->job_statuses))->map(function($jobStatus) {
            return new JobStatus($jobStatus);
        });
    }
}
