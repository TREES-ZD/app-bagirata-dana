<?php

namespace App\Services\Zendesk;

use Huddle\Zendesk\Services\ZendeskService;

class ZendeskWrapper
{
    protected $zendesk;

    public function __construct(ZendeskService $zendesk)
    {
        $this->zendesk = $zendesk;
    }

    public function listViews(...$params)
    {
        return $this->zendesk->views()->findAll(...$params);
    }

    public function listTicketsByView($viewId, ...$params)
    {
        return $this->zendesk->views($viewId)->tickets(...$params);
    }

    public function showMultipleTickets($ids, ...$params)
    {
        return $this->zendesk->tickets()->findMany($ids, ...$params);
    }

    public function updateTicket(...$params)
    {
        return $this->zendesk->tickets()->update(...$params);
    }

    public function updateManyTickets(...$params)
    {
        return $this->zendesk->tickets()->updateMany(...$params);
    }

    public function showManyJobStatuses(...$params)
    {
        return $this->zendesk->jobStatuses()->findMany(...$params);   
    }

    public function showJobStatus($job_status_id)
    {
        return $this->zendesk->get('job_statuses/'.$job_status_id);   
    }

    public function listGroups(...$params)
    {
        return $this->zendesk->groups()->findAll(...$params);
    }

    public function listGroupMemberships(...$params)
    {
        return $this->zendesk->groupMemberships()->findAll(...$params);
    }

    public function search($search_string, ...$params)
    {
        return $this->zendesk->search()->find($search_string, ...$params);
    }

    public function showTicketField(...$params)
    {
        return $this->zendesk->ticketFields()->find(...$params);
    }
}