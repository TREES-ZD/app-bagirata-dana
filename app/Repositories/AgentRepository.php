<?php

namespace App\Repositories;

use App\Traits\RoundRobinable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Repositories\Traits\Batchable;
use App\Collections\AssignmentCollection;

class AgentRepository
{
    public function updateAssignment(AssignmentCollection $assignments) {        
        $assignments->success()->groupBy('agent_id')->each(function($assignments, $agent_id) {
            Redis::sadd(sprintf("agent:%s:assignedTickets", $agent_id), ...$assignments->ticketIds()->all());
        });
    }

    public function updateUnassignment(AssignmentCollection $assignments) {
        $assignments->success()->groupBy('agent_id')->each(function($assignments, $agent_id) {
            Redis::srem(sprintf("agent:%s:assignedTickets", $agent_id), ...$assignments->ticketIds()->all());
        });
    }

    public function clear() {

    }
}