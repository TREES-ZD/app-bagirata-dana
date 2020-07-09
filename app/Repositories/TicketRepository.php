<?php

namespace App\Repositories;

use App\Agent;
use App\Services\ZendeskService;
use Illuminate\Support\Collection;
use App\Collections\AgentCollection;
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
        return $this->zendesk->getAssignableTicketsByView($viewId)->values()->all();
    }

    public function getAssignableByView($viewId) {
        return $this->zendesk->getAssignableTicketsByView($viewId);
    }

    public function getAssigned(Agent $agent) {
        return $this->zendesk->getAssignedTickets($agent)->values()->all();
    }

    public function getAssignedByAgents(AgentCollection $agents) {
        $agents = $agents->map(function($agent) {
            return $this->zendesk->getAssignedTickets($agent)->values()->all();
        });
        
        return $agents->flatten()->all();
    }

    public function assign($tickets) {
        return $this->zendesk->updateManyTickets($tickets);
    }

    public function find(array $ids) {
        return $this->zendesk->getTicketsByIds($ids)->values()->all();
    }

    public function checkJobStatus($jobStatusId) {
        return $this->zendesk->getJobStatus($jobStatusId)->job_status;
    }
}