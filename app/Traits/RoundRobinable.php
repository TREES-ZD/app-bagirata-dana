<?php

namespace App\Traits;

use App\Agent;
use App\Collections\AgentCollection;
use App\Services\Zendesk\TicketCollection;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

trait RoundRobinable
{ 
    public function createAssignments(Collection $agents, Collection $tickets, $batch) {
        if ($agents->count() < 1 || $tickets->count() < 1) {
            return collect();
        }
        
        $agentsByGroup = $agents->groupBy('zendesk_group_id');
        $ticketsByGroup = $tickets->groupBy(function ($ticket) {
            return $ticket->group_id;
        });

        $matches = collect();
        $ticketsByGroup->each(function($tickets, $group_id) use ($agentsByGroup, $matches, $agents, $batch) {
            $agents = $group_id != "" ? $agentsByGroup->get($group_id) : $agents;

            if (!$agents || $agents->count() < 1) {
                return;
            }
            
            $tickets->each(function($ticket, $index) use ($agents, $matches, $batch) {

                $agentNum = ($index % $agents->count());
                $agent = $agents->get($agentNum);
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
            });
        });
        return $matches;
    }

    public function createAssignmentsNew(AgentCollection $agents, TicketCollection $tickets, $batch) {
        if ($agents->isEmpty() || $tickets->isEmpty()) return;

        $agents = $agents->filter->status;
        $agentsByGroup = $agents->groupBy('zendesk_group_id');
        $ticketsByGroup = $tickets->filter->isAssignable()->groupBy(function ($ticket) {
            return $ticket->group_id;
        });
        $now = now();

        return collect($ticketsByGroup
                ->reject(function($tickets, $group_id) use ($agentsByGroup, $agents){
                    $agents = $agentsByGroup->get($group_id);
                    return !$agents;
                })
                ->map(function($tickets, $group_id) use ($agentsByGroup, $agents, $now, $batch) {
                    $agents = $group_id != "" ? $agentsByGroup->get($group_id) : $agents;
                    $total_tickets = $tickets->count();
                    
                    return $tickets
                            ->map(function($ticket, $index) use ($total_tickets, $now, $agents, $batch) {
                                $turn = ($index % $agents->count());
                                $assignedAgent = $agents->get($turn);
                                
                                return (object) [
                                    'agent_id' => $assignedAgent->id,
                                    'agent_fullName' => $assignedAgent->fullName,
                                    "agent_zendesk_agent_id" => $assignedAgent->zendesk_agent_id,
                                    "agent_zendesk_group_id" => $assignedAgent->zendesk_group_id,
                                    'agent_zendesk_custom_field_id' => $assignedAgent->zendesk_custom_field_id,
                                    'ticket_id' => $ticket->id,
                                    'ticket_subject' => $ticket->subject,
                                    'type' => Agent::ASSIGNMENT,
                                    "batch" => $batch
                                ];
                            });
                })
                ->flatten()->all());

    }
}
