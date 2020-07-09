<?php

namespace App\Collections;

use App\Services\Zendesk\Ticket;
use Illuminate\Support\Collection;
use App\Collections\BatchableCollection;


class AgentCollection extends Collection
{
    public function groupById() {
        return $this->groupBy(function($agent, $key) {
            return sprintf("%s-%s-%s", $agent->zendesk_agent_id, $agent->zendesk_group_id, $agent->zendesk_custom_field_id);
        })->map(function($agents) {
            return $agents->first();
        });
    }

    public function getByTicket(Ticket $ticket) {
        $customField = collect($ticket->custom_fields)->groupBy("id");
        $agentName = $customField->get(env("ZENDESK_AGENT_NAMES_FIELD", 360000282796))->first();

        $id = sprintf("%s-%s-%s", $ticket->assignee_id, $ticket->group_id, optional($agentName)->value);
        return $this->get($id);
    }
}