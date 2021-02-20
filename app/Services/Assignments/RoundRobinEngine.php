<?php

namespace App\Services\Assignments;

use App\Agent;
use App\Services\Zendesk\Ticket;
use App\Collections\AgentCollection;
use App\Services\Zendesk\TicketCollection;
use Illuminate\Support\Collection;
use Zendesk\API\Resources\Core\TicketComments;

class RoundRobinEngine
{
    const DEFAULT = "DEFAULT";
    /**
     * @var string|null
     */
    public $batch;

    /**
     * @var AgentCollection $agent
     */
    public $agents;

    public $slots;

    /**
     * @var TicketCollection $tickets
     */
    public $ticketsIndex;

    /**
     * @var Collection $agentOrders
     */
    public $agentOrders;

    public $assignedTicketIds;

    /**
     * @var Collection $assignmentPairs
     */
    public $assignmentPairs;

    public function __construct()
    {
        $this->slots = collect();
        $this->agents = new AgentCollection();
        $this->ticketsIndex = new TicketCollection();
        $this->agentOrders = collect();
        $this->assignmentPairs = collect();
        $this->currentTime = now();
    }

    public function make() {
        $self = $this;
        return $this->assignmentPairs->map(function($pairs) use ($self) {
            $agent = $self->agents->firstWhere('id', $pairs[0]);
            $ticket = $self->ticketsIndex->get($pairs[1]);
            return (object) [
                'agent_id' => $agent->id,
                'agent_fullName' => $agent->fullName,
                "agent_zendesk_agent_id" => $agent->zendesk_agent_id,
                "agent_zendesk_group_id" => $agent->zendesk_group_id,
                'agent_zendesk_custom_field_id' => $agent->zendesk_custom_field_id,
                'ticket_id' => $ticket->id,
                'ticket_subject' => $ticket->subject,
                "view_id" => $ticket->viewId(),
                'type' => Agent::ASSIGNMENT,
                "batch" => "tes",
                "created_at" => now()
            ];
        });
    }

    public function newSlot($batch, $now) 
    {
        $this->batch = $batch;
        $this->currentTime = $now;
        return $this;
    }

    public function addAssignableAgents(AgentCollection $agents) : void
    {
        $self = $this;
        $this->agents = $agents;

        // Build orders
        $this->agents
            ->groupByOrdersIdentifierTags()
            ->each(function(AgentCollection $agents, $tag) use ($self) { 
                $self->agentOrders->put($tag, $agents->getAssignmentOrders($tag));
            });
    }

    public function assign(TicketCollection $tickets): void 
    {
        $self = $this;

        $tickets->each(function(Ticket $ticket) use ($self) {
            $self->assignTicket($ticket);
        });
    }

    public function assignTicket(Ticket $ticket): bool
    {
        if (!$this->ticketsIndex->has($ticket->id())) {
            $this->ticketsIndex->put($ticket->id(), $ticket);
        }

        if (!$ticket->isAssignable()) {
            return false;
        }
        
        $agent = $this->chooseEligibleAgent($ticket);
        if ($this->checkAlreadyAssigned($ticket) || !$agent) {
            return false;
        }

        $this->rotateAgentOrders($ticket);

        $this->recordAssignment($agent, $ticket);

        return true;
    }

    public function chooseEligibleAgent(Ticket $ticket): ?Agent
    {
        $self = $this;
        $agent = null;

        $orders = $this->getAgentOrders($ticket->getOrderIdentifier());
        $agentOrderNum = $orders->first(function($order) use ($self, $ticket, &$agent) {
                            $agent = $self->agents->firstWhere('id', $order);
                            return optional($agent)->prepareAssignment($ticket);
                        });
        
        return $agentOrderNum && $agent ? $agent : null;
    }

    public function checkAlreadyAssigned(Ticket $ticket)
    {
        return $this->assignmentPairs
                ->contains(function($assignmentPair) use ($ticket) {
                    return $assignmentPair[1] == $ticket->id();
                });
    }

    public function rotateAgentOrders(Ticket $ticket) 
    {
        $orderId = (string) $ticket->getOrderIdentifier();
        $orders = $this->agentOrders->get($orderId);
        $this->agentOrders->put($orderId, $orders->rotate(1));
        return $this;
    }

    public function getAgentOrders(string $orderTag): Collection
    {
        if ($this->agentOrders->get($orderTag) instanceof Collection) {
            return $this->agentOrders->get($orderTag);
        }

        return collect();
    }

    protected function recordAssignment(Agent $agent, Ticket $ticket)
    {
        $this->assignmentPairs->add([$agent->id, $ticket->id()]);
    }
}