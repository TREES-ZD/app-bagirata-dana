<?php

namespace App\Repositories;

use App\Agent;
use App\AvailabilityLog;
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
       return $assignments->success()->groupBy(['agent_id', 'type'])->map(function($assignmentsByType, $agent_id) {

            return $assignmentsByType->each(function($assignments, $type) use ($agent_id) {
                $assignments
                        ->when($type == Agent::ASSIGNMENT, function($assignments) use ($agent_id) {
                            return Redis::sadd(sprintf("agent:%s:assignedTickets", $agent_id), ...$assignments->ticketIds()->all());
                        });

                $assignments->when($type == Agent::UNASSIGNMENT, function($assignments) use ($agent_id) {
                            return Redis::srem(sprintf("agent:%s:assignedTickets", $agent_id), ...$assignments->ticketIds()->all());
                        });
            });
            
        });
    }

    public function currentAssignmentLogs(Collection $agents) {
        return $agents->mapToGroups(function($agent) {
            return [$agent->id => Redis::smembers(sprintf("agent:%s:assignedTickets", $agent->id))];
        });
    }
    
    public function clearCurrentAssignmentLog(AgentCollection $agents) {
        $keys = $agents->map(function($agent) {
            return 'agent:'.$agent->id.':assignedTickets';
        })->all();

        return Redis::command('del', $keys);
    }

    /**
     * Get agents who have been unavailable for at least for 2 minutes, and at most 30 minutes so we can retry
     *
     * @return AgentCollection
     */
    public function getUnassignEligible() {
        $unassignEligibleAgentIds = AvailabilityLog::whereBetween('created_at', [now()->subMinutes(30), now()])
                        ->latest()
                        ->get()
                        ->groupBy('agent_id')
                        ->map
                        ->first()
                        ->filter(function($log) { 
                            return $log->status == "Unavailable";
                        })
                        ->pluck('agent_id');

        return Agent::find($unassignEligibleAgentIds);
    }
}