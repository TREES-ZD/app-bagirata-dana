<?php

namespace App\Repositories;

use App\Agent;
use App\Traits\RoundRobinable;
use Illuminate\Support\Collection;
use App\Collections\AgentCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Repositories\Traits\Batchable;
use App\Collections\AssignmentCollection;

class AgentRepository
{
    public function updateCurrentAssignmentLog(AssignmentCollection $assignments) {
        $assignments->onlyAssignment()->success()->groupBy('agent_id')->each(function($assignments, $agent_id) {
            Redis::srem(sprintf("agent:%s:assignedTickets", $agent_id), ...$assignments->ticketIds()->all());
        });

        $assignments->onlyUnassignment()->success()->groupBy('agent_id')->each(function($assignments, $agent_id) {
            Redis::srem(sprintf("agent:%s:assignedTickets", $agent_id), ...$assignments->ticketIds()->all());
        });
    }

    public function clearCurrentAssignmentLog(AgentCollection $agents) {
        $keys = $agents->map(function($agent) {
            return 'agent:'.$agent->id.':assignedTickets';
        })->all();

        return Redis::command('del', $keys);
    }

    public function getUnassignable() {
        return new AgentCollection(Agent::all()->all());
    }

    public function getAvailable() {
        $agents = $this->rules()
        ->disableCache()
        ->where('status', true)
        ->get();

        return $agents->sortBy(function($a) {
                    return $a->assignments->last() ? $a->assignments->last()->created_at->timestamp : 1;
                })->values();
    }
}