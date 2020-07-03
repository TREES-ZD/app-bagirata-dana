<?php

namespace App\Collections;

use Illuminate\Support\Collection;
use App\Collections\BatchableCollection;


class TicketCollection extends Collection
{
    public function areAllAssigned() {
        return $this->every(function($ticket) {
            $customField = collect($ticket->custom_fields)->groupBy("id");
            $agentName = optional($customField->get(env("ZENDESK_AGENT_NAMES_FIELD", 360000282796)))->first();

            return $ticket->assignee_id && $ticket->group_id && optional($agentName)->value;
        });
    }

    public function areAllUnassigned() {
        return $this->every(function($ticket) {
            $customField = collect($ticket->custom_fields)->groupBy("id");
            $agentName = optional($customField->get(env("ZENDESK_AGENT_NAMES_FIELD", 360000282796)))->first();

            return !$ticket->assignee_id && !optional($agentName)->value;
        });
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