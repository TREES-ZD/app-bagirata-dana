<?php

namespace App\Services\Assignments;

use App\Agent;
use App\Assignment;
use App\Services\Zendesk\Ticket;
use InvalidAssignmentTypeException;

class PreparedAssignment
{
    /** @var Agent $agent */
    public $agent;
    public $ticket;
    public $viewId;
    public $type;
    public $batch;
    public $created_at;

    public function __construct(Agent $agent, Ticket $ticket, $viewId, $type, $batch, $created_at)
    {
        $this->agent = $agent;
        $this->ticket = $ticket;
        $this->viewId = $viewId;
        $this->type = $type;
        $this->batch = $batch;
        $this->created_at = $created_at;
    }

    public function display()
    {
        return (object) [
            'agent_id' => $this->agent->id,
            'agent_fullName' => $this->agent->fullName,
            "agent_zendesk_agent_id" => $this->agent->zendesk_agent_id,
            "agent_zendesk_group_id" => $this->agent->zendesk_group_id,
            'agent_zendesk_custom_field_id' => $this->agent->zendesk_custom_field_id,
            'ticket_id' => $this->ticket->id,
            'ticket_subject' => $this->ticket->subject,
            "view_id" => $this->ticket->viewId(),
            'type' => $this->type,
            "batch" => $this->batch,
            "created_at" => $this->created_at
        ];
    }

    public function toAssignment(): Assignment
    {
        $attributes = [
            "type" => $this->type,
            "batch_id" => $this->batch,
            "agent_id" => $this->agent->id,
            "agent_name" => $this->agent->fullName,
            "zendesk_view_id" => $this->ticket->viewId() ?? "viewId",
            "zendesk_ticket_id" => $this->ticket->id(),
            "zendesk_ticket_subject" => $this->ticket->subject,
            "response_status" => Assignment::RESPONSE_STATUS_PENDING,
            "created_at" => $this->created_at
        ];
        return new Assignment($attributes);
    }

    public function toBody(): ?array
    {
        if ($this->type == Assignment::TYPE_ASSIGNMENT || $this->type == Assignment::TYPE_RETRIED_ASSIGNMENT || $this->type == Assignment::TYPE_REASSIGNMENT) {
            return [
                "id" => $this->ticket->id,
                "assignee_id" => $this->agent->zendesk_agent_id,
                "group_id" => $this->agent->zendesk_group_id,
                "custom_fields" => [
                    [
                        "id" => env("ZENDESK_AGENT_NAMES_FIELD", 360000282796),
                        "value" => $this->agent->zendesk_custom_field_id
                    ]
                ]
            ];    
        }
        if ($this->type == Assignment::TYPE_UNASSIGNMENT || $this->type == Assignment::TYPE_OBSERVED_UNASSIGNMENT) {
            return [
                "id" => $this->ticket->id,
                "custom_fields" => [
                    [
                    "id" => env("ZENDESK_AGENT_NAMES_FIELD", 360000282796),
                    "value" => null
                    ]
                ],
                "comment" =>  [
                    "body" => $this->type == Assignment::TYPE_OBSERVED_UNASSIGNMENT ? "BAGIRATA Observed Unassignment: " . $this->agent->fullName : "BAGIRATA Agent Unavailable: " . $this->agent->fullName,
                    "author_id" => $this->agent->zendesk_agent_id,
                    "public" => false
                ]
            ];
        }
    }
}