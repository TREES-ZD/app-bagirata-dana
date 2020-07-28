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

            error_log('TICKET BY GROUP');
            error_log(print_r($ticket->group_id, true));

            if (!$agents || $agents->count() < 1) {
                return;
            }

            $tickets->each(function($ticket, $index) use ($agents, $matches, $batch) {

                $agentNum = ($index % $agents->count());
                $agent = $agents->get($agentNum);

                error_log('TESTING');
                error_log(print_r($ticket->id, true));
                error_log(print_r($ticket->group_id, true));
                error_log(print_r($agent->zendesk_group_id, true));
                

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
                    $sameGroupId = $agent->zendesk_group_id == $ticket->group_id;
                    // error_log("TICKET GROUP NOT NULL");
                    // error_log(print_r($ticket->id, true));
                    // error_log(print_r($ticket->group_id, true));
                    // error_log(print_r($agent->zendesk_group_id, true));
                    // error_log(print_r($sameGroupId, true));

                    if ($agent->zendesk_group_id == $ticket->group_id) {
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
                    }
                }
            });

        });
        return $matches;
    }
}
