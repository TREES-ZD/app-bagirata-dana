<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

trait RoundRobinable
{ 
    public function createAssignments(Collection $agents, Collection $tickets) {
        if ($agents->count() < 1 || $tickets->count() < 1) {
            return collect();
        }
        
        $agentsByGroup = $agents->groupBy('zendesk_group_id');
        $ticketsByGroup = $tickets->groupBy('group_id');

        $matches = collect();
        $ticketsByGroup->each(function($tickets, $group_id) use ($agentsByGroup, $matches, $agents) {
            $agents = $group_id != "" ? $agentsByGroup->get($group_id) : $agents;

            if (!$agents || $agents->count() < 1) {
                return;
            }

            $tickets->each(function($ticket, $index) use ($agents, $matches) {

                $agentNum = ($index % $agents->count());
                $agent = $agents->get($agentNum);
                $matches->add(collect([
                    "agent" => $agent,
                    "ticket" => $ticket
                ]));
            });

        });
        return $matches;
    }
}
