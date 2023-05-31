<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Assignment extends Model
{
    protected $fillable = ["type", "subtype", "zendesk_view_id", "batch_id", "agent_id", "agent_name", "zendesk_ticket_id", "zendesk_ticket_subject", "zendesk_ticket_created_at", "zendesk_ticket_updated_at", "zendesk_ticket_status", "zendesk_ticket_requester_id", "zendesk_job_id","zendesk_job_message", "assigned_at", "response_status", "response_error", "response_details", "created_at"];    
    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
