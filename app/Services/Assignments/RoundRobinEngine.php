<?php

namespace App\Services\Assignments;

use App\Agent;
use App\Assignment;
use App\Services\Zendesk\Ticket;
use Illuminate\Support\Collection;
use App\Collections\AgentCollection;
use App\Collections\AssignmentCollection;
use App\Services\Zendesk\TicketCollection;
use App\Services\Assignments\PreparedAssignment;

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

    /**
     * @var Collection $retriedAssignments
     */
    public $retriedAssignments;

    public function __construct()
    {
        $this->slots = collect();
        $this->agents = new AgentCollection();
        $this->ticketsIndex = new TicketCollection();
        $this->agentOrders = collect();
        $this->assignmentPairs = collect();
        $this->retriedAssignments = collect();
        $this->currentTime = now();
    }

    /**
     * @return Collection
     */
    public function make()
    {
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
                'type' => Assignment::TYPE_ASSIGNMENT,
                "batch" => "tes",
                "created_at" => now()
            ];
        });
    }

    public function makePreparedAssignments(): PreparedAssignmentCollection
    {
        $self = $this;
        return (new PreparedAssignmentCollection($this->assignmentPairs->all()))->map(function($pairs) use ($self) {
            $agent = $self->agents->firstWhere('id', $pairs[0]);
            $ticket = $self->ticketsIndex->get($pairs[1]);
            return (new PreparedAssignment($agent, $ticket,$ticket->viewId(), $pairs[2], $this->batch, now()));
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
        // $this->agents
        //     ->groupByOrdersIdentifierTags()
        //     ->each(function(AgentCollection $agents, $tag) use ($self) { 
        //         // $self->agentOrders->put($tag, $agents->getAssignmentOrders($tag));
        //         $self->agentOrders->put($tag, $agents->pluck('id'));
        //     });
    }

    public function setAgentOrders(Collection $agentOrders): RoundRobinEngine
    {
        $this->agentOrders = $agentOrders;

        return $this;
    }

    public function setOrdersPerView(AssignmentCollection $latestAssignmentsPerView): RoundRobinEngine
    {
        $latestAssignmentsPerView = optional($latestAssignmentsPerView->sortBy('id'))->values() ?: AssignmentCollection::make();

        $ordersByView = $latestAssignmentsPerView->mapToGroups(function(Assignment $assignment) {
            return [$assignment->zendesk_view_id => $assignment->agent_id];
        });
        $defaultOrder = $latestAssignmentsPerView->mapWithKeys(function(Assignment $assignment) {
            return [$assignment->agent_id => $assignment->id];
        })->sort()->keys();

        $newOrders = $this->agentOrders->filterMap(function($order, $tag) use ($ordersByView, $defaultOrder) {
            $tag = (new OrderTag())->parseTag($tag);

            $selectedOrder = $tag->viewId ? ($ordersByView->get($tag->viewId) ?: collect()) : $defaultOrder;
 
            $agentIdsNotInAssignments = $order->diff($selectedOrder)->values();

            return $agentIdsNotInAssignments->merge($selectedOrder);
        });

        $this->agentOrders = $this->agentOrders->merge($newOrders);

        return $this;
    }

    public function assignWithPriority(TicketCollection $tickets): void
    {
        $recentlyFailedTickets = $tickets->whereIn('id', $this->retriedAssignments->keys()->all());
        
        $this->assign($recentlyFailedTickets);
        $this->assign($tickets->prioritize());
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

        if ($agent = $this->isRetriableTicket($ticket)) {
            $this->recordAssignment($agent, $ticket, Assignment::TYPE_RETRIED_ASSIGNMENT);
            return true;
        }
        
        $agent = $this->chooseEligibleAgent($ticket);
        if ($this->checkAlreadyAssigned($ticket) || !$agent) {
            return false;
        }

        $this->rotateAgentOrders($ticket);

        $this->recordAssignment($agent, $ticket);

        return true;
    }

    public function addRetriedAssignments(AssignmentCollection $assignments): void
    {
        $self = $this;
        $assignments->each(function(Assignment $assignment) use ($self) {
            $self->retriedAssignments->put($assignment->zendesk_ticket_id, $assignment->agent_id);
        });
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

    public function checkAlreadyAssigned(Ticket $ticket): bool
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

    public function isRetriableTicket(Ticket $ticket): ?Agent
    {
        if ($agentId = $this->retriedAssignments->get($ticket->id)) {
            return $this->agents->firstWhere('id', $agentId);
        }

        return null;
    }

    public function recordAssignment(Agent $agent, Ticket $ticket, $type = Assignment::TYPE_ASSIGNMENT): void
    {
        $this->assignmentPairs->add([$agent->id, $ticket->id(), $type]);
    }
}