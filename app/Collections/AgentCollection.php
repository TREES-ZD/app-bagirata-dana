<?php

namespace App\Collections;

use App\Agent;
use App\Services\Zendesk\Ticket;
use App\Collections\BatchableCollection;
use App\Services\Agents\OrderTag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class AgentCollection extends Collection
{
    public function groupById() {
        return $this->groupBy(function($agent, $key) {
            return sprintf("%s-%s-%s", $agent->zendesk_agent_id, $agent->zendesk_group_id, $agent->zendesk_custom_field_id);
        })->map(function($agents) {
            return $agents->first();
        });
    }

    public function getByTicket(Ticket $ticket) {
        $customField = collect($ticket->custom_fields)->groupBy("id");
        $agentName = $customField->get(env("ZENDESK_AGENT_NAMES_FIELD", 360000282796))->first();

        $id = sprintf("%s-%s-%s", $ticket->assignee_id, $ticket->group_id, optional($agentName)->value);
        
        return $this->get($id);
    }

    /**
     * group of view Ids and each agents
     */
    public function groupByViews() : AgentCollection
    {
        return $this->groupBy->assignedViewIds();
    }

    public function groupByGroups(): AgentCollection
    {
        return $this->groupBy->zendeskGroupId();
    }

    public function groupByOrdersIdentifierTags()
    {
        return $this->groupBy(function(Agent $agent) {
            return $agent->getOrderIdentifierTags()->map->__toString()->all();
        });
    }

    public function getAssignmentOrders(string $orderTag) : SupportCollection
    {
        return $this->sort(function(Agent $a, Agent $b) use ($orderTag) {
            $aOrder = $a->latestAssignmentOrder($orderTag);
            $bOrder = $b->latestAssignmentOrder($orderTag);
            
            if ($aOrder == $bOrder) return 0;

            if ($aOrder == null) return 1;

            if ($bOrder == null) return -1;
        
            if ($aOrder > $bOrder) return 1;

            return -1;
        })->pluck('id');
    }
}