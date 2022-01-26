<?php

namespace App\Services\Assignments;

use Stringable;
use Illuminate\Support\Str;

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

    public function parseTag(?string $tag): OrderTag
    {
        if (Str::contains($tag, ["viewId:", "groupId:"])) {
            $ids = collect(explode("-", $tag));
            $viewId = $ids->first(function($id) {
                return Str::startsWith($id, "viewId:");
            });
            $groupId = $ids->first(function($id) {
                return Str::startsWith($id, "groupId:");
            });

            $this->viewId = $viewId ? Str::after($viewId, "viewId:") : null;
            $this->groupId = $groupId ? Str::after($groupId, "groupId:") : null;
            return $this;
        }
        
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