<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Assignment extends Model
{
    protected $fillable = ["type", "zendesk_view_id", "batch_id", "agent_id", "agent_name", "zendesk_ticket_id", "zendesk_ticket_subject", "response_status", "created_at"];    
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
