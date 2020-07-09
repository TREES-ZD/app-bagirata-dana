<?php

namespace App\Services\Zendesk;

use Illuminate\Support\Collection;
use App\Collections\BatchableCollection;


class TicketCollection extends Collection
{
    public function areAllAssigned() {
        return $this->every->assigned();
    }

    public function areAllUnassigned() {
        return $this->every->unassigned();
    }

    public function checkForUpdate() {
        $ids = $this->pluck('id')->values()->all();
        $tickets = app('App\Services\ZendeskService')->getTicketsByIds($ids);
        return new self($tickets->all());
    }

    public function unassign() {
        $ids = $this->pluck('id')->values()->all();
        return app('App\Services\ZendeskService')->unassignTickets($ids, );
    }
}