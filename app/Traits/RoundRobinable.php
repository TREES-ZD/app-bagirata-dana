<?php

namespace App\Traits;

use App\Agent;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

trait RoundRobinable
{ 
    public function createAssignments(Collection $agents, Collection $tickets, $batch) {
        if ($agents->count() < 1 || $tickets->count() < 1) {
            return collect();
        }
        
        $agentsByGroup = $agents->groupBy('zendesk_group_id');
        $ticketsByGroup = $tickets->groupBy('group_id');

        $matches = collect();
        $ticketsByGroup->each(function($tickets, $group_id) use ($agentsByGroup, $matches, $agents, $batch) {
            $agents = $group_id != "" ? $agentsByGroup->get($group_id) : $agents;

            if (!$agents || $agents->count() < 1) {
                return;
            }

            $tickets->each(function($ticket, $index) use ($agents, $matches, $batch) {

                $agentNum = ($index % $agents->count());
                $agent = $agents->get($agentNum);

                /* TESTING DIAS */
                if ($ticket->group_id === null) {
                    $matches->add((object) [
                        'agent_id' => $agent->id,
                        'agent_fullName' => $agent->fullName,
                        "agent_zendesk_agent_id" => $agent->zendesk_agent_id,
                        "agent_zendesk_group_id" => $agent->zendesk_group_id,
                        'agent_zendesk_custom_field_id' => $agent->zendesk_custom_field_id,
                        'ticket_id' => $ticket->id,
                        'ticket_subject' => $ticket->subject,
                        'type' => Agent::ASSIGNMENT,
                        "batch" => $batch
                    ]);
                } else {
                    if ($agent->zendesk_group_id === $ticket->group_id) {
                        $matches->add((object) [
                            'agent_id' => $agent->id,
                            'agent_fullName' => $agent->fullName,
                            "agent_zendesk_agent_id" => $agent->zendesk_agent_id,
                            "agent_zendesk_group_id" => $agent->zendesk_group_id,
                            'agent_zendesk_custom_field_id' => $agent->zendesk_custom_field_id,
                            'ticket_id' => $ticket->id,
                            'ticket_subject' => $ticket->subject,
                            'type' => Agent::ASSIGNMENT,
                            "batch" => $batch
                        ]);
                    } else {
                        error_log('error')
                        error_log(print_r($agent->zendesk_group_id, TRUE));
                        error_log(print_r($ticket->group_id, TRUE));
                    }
                }
            });

        });
        return $matches;
    }
}
