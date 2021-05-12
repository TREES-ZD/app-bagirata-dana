<?php

namespace App;

use App\Collections\AssignmentCollection;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    public const TYPE_ASSIGNMENT = "ASSIGNMENT";
    public const TYPE_RETRIED_ASSIGNMENT = "RETRIED_ASSIGNMENT";
    public const TYPE_REASSIGNMENT = "REASSIGNMENT";
    public const TYPE_UNASSIGNMENT = "UNASSIGNMENT";
    public const TYPE_OBSERVED_UNASSIGNMENT = "OBSERVED_UNASSIGNMENT";

    public const RESPONSE_STATUS_SUCCESS = "200";
    public const RESPONSE_STATUS_FAILED = "FAILED";
    public const RESPONSE_STATUS_PENDING = "PENDING";

    protected $fillable = ["type", "zendesk_view_id", "batch_id", "agent_id", "agent_name", "zendesk_ticket_id", "zendesk_ticket_subject", "response_status", "created_at"];

    public function newCollection(array $models = [])
    {
        return new AssignmentCollection($models);
    }
}
