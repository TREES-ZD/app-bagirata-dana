<?php

namespace App\Repositories;

use App\Agent;
use App\Assignment;
use App\Collections\AgentCollection;
use App\Traits\RoundRobinable;
use Illuminate\Support\Collection;
use App\Collections\TicketCollection;
use Illuminate\Support\Facades\Cache;
use App\Repositories\Traits\Batchable;
use App\Collections\AssignmentCollection;

class AssignmentRepository
{
    use RoundRobinable, Batchable;

    protected $cachePrefix = "assignments";

    protected $ticketRepository;

    public function __construct(TicketRepository $ticketRepository)
    {
        $this->ticketRepository = $ticketRepository;
    }

    public function retrieveAssignments($batch) {
        return new AssignmentCollection($this->cache($batch)->all());
    }

    public function makeAssignments($batch, Collection $tasks) {
        return (new AssignmentCollection($tasks->all()))->map(function($task) use ($batch) {
            $agents = $task->getAvailableAgents();
            $tickets = $this->ticketRepository->getAssignableByView($task->zendesk_view_id);
            // $previousFailedAssignments = Assignment::where('response_status', '!=', '200')->where('type', 'ASSIGNMENT')->where('created_at', '>', now()->subMinutes(10))->get(); // TODO: tes jika agent offline (reassign) terus online lagi
            
            return $this->createAssignments($agents, $tickets, $batch);
        })->flatten();        
    }

    public function makeUnassignments($batch, AgentCollection $agents) {
        $tickets = $this->ticketRepository->getAssignedByAgents($agents);

        $agentDictionary = $agents->groupById();
        $unassignments = $tickets->map(function($ticket) use ($agentDictionary, $batch) {
            $agent = $agentDictionary->getByTicket($ticket);
            
            if (!$agent) {
                return;
            }

            return (object) [
                'agent_id' => $agent->id,
                'agent_fullName' => $agent->fullName,
                "agent_zendesk_agent_id" => $agent->zendesk_agent_id,
                "agent_zendesk_group_id" => $agent->zendesk_group_id,
                'agent_zendesk_custom_field_id' => $agent->zendesk_custom_field_id,
                'ticket_id' => $ticket->id,
                'ticket_subject' => $ticket->subject,
                'type' => Agent::UNASSIGNMENT,
                'batch' => $batch,
                'created_at' => now()
            ];
        })->reject(function($ticket) {
            return !$ticket;
        });
        
        return $unassignments;
    }

    public function prepareAssignment($batch, Collection $tasks) {
        $assignments = $this->makeAssignments($batch, $tasks);

        return new AssignmentCollection($this->cache($batch, $assignments)->all());
    }
    
    public function prepareUnassignment($batch, AgentCollection $agents) {
        $unassigments = $this->makeUnassignments($batch, $agents);

        return new AssignmentCollection($this->cache($batch, $unassigments)->all());
    }
}