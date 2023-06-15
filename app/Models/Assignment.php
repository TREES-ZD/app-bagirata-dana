<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Assignment extends Model
{
    protected $fillable = ["type", "subtype", "zendesk_view_id", "batch_id", "agent_id", "agent_name", "zendesk_ticket_id", "zendesk_ticket_subject", "zendesk_ticket_created_at", "zendesk_ticket_updated_at", "zendesk_ticket_status", "zendesk_ticket_requester_id", "zendesk_ticketvia_channel", "zendesk_ticket_from_messaging_channel", "zendesk_job_id","zendesk_job_message", "assigned_at", "response_status", "response_error", "response_details", "created_at"];    
    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    public function assignedDate() {
        preg_match("/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/", $this->zendesk_job_message, $matches);
        $dateTime = isset($matches[0]) ? now()->parse($matches[0])->addHours(7) : $this->created_at;
        return (string) $dateTime;
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
