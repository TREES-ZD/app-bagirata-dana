<?php

namespace App\Repositories;

use App\Agent;
use App\Services\ZendeskService;
use Illuminate\Support\Collection;
use App\Collections\TicketCollection;
use Illuminate\Support\Facades\Cache;
use App\Repositories\Traits\Batchable;
use App\Collections\AssignmentCollection;

class TicketRepository
{
    use Batchable;

    public $zendesk;

    protected $cachePrefix = "tickets";

    public function __construct(ZendeskService $zendesk)
    {
        $this->zendesk = $zendesk;
    }

    public function getAssignableTicketsByView($viewId) {
        return new TicketCollection($this->zendesk->getAssignableTicketsByView($viewId)->values()->all());
    }

    public function getAssigned(Agent $agent) {
        return new TicketCollection($this->zendesk->getAssignedTickets($agent)->values()->all());
    }

    public function find(array $ids) {
        return new TicketCollection($this->zendesk->getTicketsByIds($ids)->values()->all());
    }

    public function checkJobStatus($jobStatusId) {
        return $this->zendesk->getJobStatus($jobStatusId)->job_status;
    }
}