<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $fillable = ["type", "agent_id", "agent_name", "ticket_id", "ticket_name", "created_at"];
}
