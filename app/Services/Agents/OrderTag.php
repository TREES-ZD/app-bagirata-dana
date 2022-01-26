<?php

namespace App\Services\Agents;

use App\Agent;
use App\Services\Zendesk\Ticket;
use App\Collections\AgentCollection;
use App\Services\Zendesk\TicketCollection;
use Illuminate\Support\Collection;
use Stringable;
use Zendesk\API\Resources\Core\TicketComments;

class OrderTag implements Stringable
{
    public $viewId;

    public $groupId;

    public $name;
        
    public function __construct($viewId = null, $groupId = null)
    {
        $this->viewId = $viewId;
        $this->groupId = $groupId;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function parseTag($tag)
    {
        return $this;
    }

    public function __toString()
    {
        if ($this->name) {
            return $this->name;
        }

        if ($this->viewId && $this->groupId) {
            return sprintf("viewId:%s-groupId:%s", $this->viewId, $this->groupId);
        }
        
        if ($this->viewId) {
            return "viewId:$this->viewId";
        }

        if ($this->groupId) {
            return "groupId:$this->groupId";
        }
    }
}