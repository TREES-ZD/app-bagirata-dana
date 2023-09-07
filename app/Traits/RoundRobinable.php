<?php

namespace App\Traits;

use App\Models\Agent;
use App\Services\Zendesk\Ticket;
use Illuminate\Support\Collection;
use App\Collections\AgentCollection;
use Illuminate\Database\Eloquent\Model;
use App\Services\Zendesk\TicketCollection;

trait RoundRobinable
{ 
    public function createAssignmentsOld(Collection $agents, Collection $tickets, $batch) {
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
                    "batch" => $batch,
                    "created_at" => now()->addSeconds($index)
                ]);
            });
        });
        return $matches;
    }

    public function createAssignments(AgentCollection $agents, TicketCollection $tickets, $batch, Collection $failedAssignments = null, $view_id = "viewId", Collection $reservedAssignments = null) {
        $failedAssignments = $failedAssignments ?: collect(); 
        if ($agents->isEmpty() || $tickets->isEmpty()) return collect();

        // $agents = $agents->filter->status;
        $agents = $agents->filter(fn($agent) => $agent->custom_status == Agent::CUSTOM_STATUS_AVAILABLE);
        $agentsByGroup = $agents->groupBy('zendesk_group_id');
        $assignableTickets = $tickets->filter->isAssignable();
        $reservableFailedAssignments = $failedAssignments
                                        ->unique('zendesk_ticket_id')
                                        ->filter(function($assignment) use ($agents, $tickets) {
                                            return in_array($assignment->agent_id, $agents->pluck('id')->all()) && in_array($assignment->zendesk_ticket_id, $tickets->pluck('ticket.id')->all());
                                        });
        
        $reservedAssignments = $reservedAssignments
                                    ->unique('zendesk_ticket_id')
                                    ->reject(fn($assignment) => $reservableFailedAssignments->pluck('zendesk_ticket_id')->contains($assignment->zendesk_ticket_id))
                                    ->filter(function($assignment) use ($agents, $tickets) {
                                        return in_array($assignment->agent_id, $agents->pluck('id')->all()) && in_array($assignment->zendesk_ticket_id, $tickets->pluck('ticket.id')->all());
                                    });

        $now = now();
        $nowSubMinute = now()->subMinute();
        $nowSubTwoMinute = now()->subMinutes(2);

        $priorityAssignments = $reservableFailedAssignments
                                    ->merge($reservedAssignments)
                                    ->map(function($assignment) use ($batch, $agents, $nowSubTwoMinute, $view_id) {
                                        $assignedAgent = $agents->firstWhere('id', $assignment->agent_id);

                                        return (object) [
                                            'agent_id' => $assignedAgent->id,
                                            'agent_fullName' => $assignedAgent->fullName,
                                            "agent_zendesk_agent_id" => $assignedAgent->zendesk_agent_id,
                                            "agent_zendesk_group_id" => $assignedAgent->zendesk_group_id,
                                            'agent_zendesk_custom_field_id' => $assignedAgent->zendesk_custom_field_id,
                                            'ticket_id' => $assignment->zendesk_ticket_id,
                                            'ticket_subject' => $assignment->zendesk_ticket_subject,
                                            'ticket_created_at' => $assignment->zendesk_ticket_created_at,
                                            'ticket_updated_at' => $assignment->zendesk_ticket_updated_at,
                                            'ticket_status' => $assignment->zendesk_ticket_status,
                                            'ticket_requester_id' => $assignment->zendesk_ticket_requester_id,
                                            'ticket_via_channel' => $assignment->zendesk_ticket_via_channel,
                                            'ticket_from_messaging_channel' => $assignment->zendesk_ticket_from_messaging_channel,
                                            'assigned_at' => $assignment->assigned_at,
                                            'view_id' => $view_id,
                                            'type' => Agent::ASSIGNMENT,
                                            "batch" => $batch,
                                            "created_at" => $nowSubTwoMinute
                                        ];
                                    }); 

        $newTickets = $assignableTickets->reject(function(Ticket $ticket) use ($priorityAssignments) {
            return in_array($ticket->id, $priorityAssignments->pluck('ticket_id')->all());
        });
        
        $ticketsByGroup = $newTickets->groupBy(function (Ticket $ticket) {
            return $ticket->group_id;
        });

        $newAssignments = $ticketsByGroup
                ->reject(function($tickets, $group_id) use ($agentsByGroup, $agents){
                    $agents = $group_id != "" ? $agentsByGroup->get($group_id) : $agents;
                    return !$agents;
                })
                ->map(function($tickets, $group_id) use ($agentsByGroup, $agents, $now, $nowSubMinute, $batch, $view_id) {
                    $agents = $group_id != "" ? $agentsByGroup->get($group_id) : $agents;
                    $total_tickets = $tickets->count();
                    
                    return $tickets
                            ->map(function($ticket, $index) use ($total_tickets, $now, $nowSubMinute, $agents, $batch, $view_id) {
                                $total_agents = $agents->count();
                                $turn = ($index % $agents->count());
                                $assignedAgent = $agents->get($turn);

                                $excessTotal = $total_tickets % $total_agents;
                                $hasExcess = $total_tickets > $total_agents;
                                $lastTicketFromAgent = $index > $total_tickets - $excessTotal - 1 && $hasExcess;
                                
                                return (object) [
                                    'agent_id' => $assignedAgent->id,
                                    'agent_fullName' => $assignedAgent->fullName,
                                    "agent_zendesk_agent_id" => $assignedAgent->zendesk_agent_id,
                                    "agent_zendesk_group_id" => $assignedAgent->zendesk_group_id,
                                    'agent_zendesk_custom_field_id' => $assignedAgent->zendesk_custom_field_id,
                                    'ticket_id' => $ticket->id,
                                    'ticket_subject' => $ticket->subject,
                                    'ticket_created_at' => $ticket->created_at,
                                    'ticket_updated_at' => $ticket->updated_at,
                                    'ticket_status' => $ticket->status,
                                    'ticket_requester_id' => $ticket->requester_id,
                                    'ticket_from_messaging_channel' => $ticket->from_messaging_channel,
                                    'ticket_via_channel' => optional($ticket->via)->channel,
                                    'assigned_at' => $ticket->assigned_at,
                                    'view_id' => $view_id,
                                    'type' => Agent::ASSIGNMENT,
                                    "batch" => $batch,
                                    "created_at" => !$lastTicketFromAgent && $hasExcess ? $nowSubMinute : $now
                                ];
                            });
                })
                ->flatten()->all();

        return $priorityAssignments->merge($newAssignments);
    }
}
