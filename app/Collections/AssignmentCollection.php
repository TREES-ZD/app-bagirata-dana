<?php

namespace App\Collections;

use App\Assignment;
use Illuminate\Support\Collection;

class AssignmentCollection extends Collection
{
    protected $name = "assignments";

    public function ticketIds() {
        return $this->pluck('ticket_id')->values();
    }

    public function onlyAssignment() {
        return $this->where('type', Assignment::TYPE_ASSIGNMENT);
    }

    public function onlyUnassignment() {
        return $this->where('type', Assignment::TYPE_ASSIGNMENT);
    }

    public function success() {
        return $this->where('status', 200);
    }
}