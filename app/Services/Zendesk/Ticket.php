<?php

namespace App\Services\Zendesk;

use App\Task;
use App\Agent;
use App\Services\Assignments\OrderTag;

class Ticket
{
    public $ticket;

    public $latestAssignedView;

    public $view_id;

    /**
     * @var ?Task
     */
    public $task;

    public function __construct($ticket)
    {
        $this->ticket = $ticket;
    }

    public function id() {
        return $this->ticket->id;
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

    public function isAssignable() {
        return optional($this->ticket)->assignee_id == null && in_array(optional($this->ticket)->status, ["new", "open", "pending"]);
    }

    public function customFieldValue() {
        $customField = collect($this->ticket->custom_fields)->groupBy("id");
        $agent = optional($customField->get(env("ZENDESK_AGENT_NAMES_FIELD", 360000282796)))->first();
        return optional($agent)->value;
    }

    public function view(): Task 
    {
        return $this->latestAssignedView ?: Task::first();
    }

    public function taskOrder(): int
    {
        $position = optional($this->task)->zendesk_view_position;
        return $position ?: 999999;
    }

    public function viewId(): ?string 
    {
        return optional($this->task)->zendesk_view_id;
    }

    public function groupId(): ?int
    {
        return isset($this->ticket->group_id) ? $this->ticket->group_id : null;
    }

    public function getOrderIdentifier(): OrderTag
    {
        return new OrderTag($this->viewId() ?: Agent::DEFAULT_VIEW, $this->groupId() ?: Agent::DEFAULT_GROUP);
    }

    public function __get($name)
    {
        return $this->ticket->$name;
    }
}