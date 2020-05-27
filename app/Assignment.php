<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $fillable = ["type", "zendesk_view_id", "batch_id", "agent_id", "agent_name", "ticket_id", "ticket_name", "response_status", "created_at"];
}
