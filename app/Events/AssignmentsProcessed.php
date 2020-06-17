<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssignmentsProcessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $jobStatus;

    public $assignments;

    public $viewId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($jobStatus, $assignments, $viewId)
    {
        $this->jobStatus = $jobStatus;
        $this->assignments = $assignments;
        $this->viewId = $viewId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
