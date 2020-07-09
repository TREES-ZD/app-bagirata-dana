<?php

namespace App\Services\Zendesk;

class Ticket
{
    public $ticket;

    public function __construct($ticket)
    {
        $this->ticket = $ticket;
    }

    public function unassigned() {
        $customField = collect($this->ticket->custom_fields)->groupBy("id");
        $agentName = optional($customField->get(env("ZENDESK_AGENT_NAMES_FIELD", 360000282796)))->first();

        return !$this->ticket->assignee_id && !optional($agentName)->value;
    }

    public function assigned() {
        $customField = collect($this->ticket->custom_fields)->groupBy("id");
        $agentName = optional($customField->get(env("ZENDESK_AGENT_NAMES_FIELD", 360000282796)))->first();

        return $this->ticket->assignee_id && $this->ticket->group_id && optional($agentName)->value;
    }

    public function __get($name)
    {
        return $this->ticket->$name;
    }
}