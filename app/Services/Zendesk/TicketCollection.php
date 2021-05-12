<?php

namespace App\Services\Zendesk;

use Illuminate\Support\Collection;

class TicketCollection extends Collection
{
    public function areAllAssigned(): TicketCollection
    {
        return $this->every->assigned();
    }

    public function areAllUnassigned(): TicketCollection 
    {
        return $this->every->unassigned();
    }

    public function withView(): TicketCollection
    {
        return $this->filter(function(Ticket $ticket) {
            return $ticket->viewId();
        });
    }

    public function withoutView(): TicketCollection
    {
        return $this->filter(function(Ticket $ticket) {
            return !$ticket->viewId();
        });
    }

    public function withGroup(): TicketCollection
    {
        return $this->filter(function(Ticket $ticket) {
            return $ticket->groupId();
        });
    }

    public function withoutGroup(): TicketCollection
    {
        return $this->filter(function(Ticket $ticket) {
            return !$ticket->groupId();
        });
    }

    public function groupByView(): TicketCollection
    {
        return $this->groupBy->viewId();
    }

    public function groupByGroups(): TicketCollection 
    {
        return $this->groupBy->groupId();
    }

    /**
     * prioritikan tiket yang di view lalu yang ada groupnya
     *
     * @return TicketCollection
     */
    public function prioritize(): TicketCollection
    {
        return $this->sortBy(function(Ticket $a,$b) {
            return [
                $a->taskOrder(),
                $a->groupId() ?: 999999
            ];
        })->values();
    }

    public function recentlyFailed(): TicketCollection
    {
        return $this->filter(function() {
            return false;
        });
    }
}