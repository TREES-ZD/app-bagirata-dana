<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UnassignmentsProcessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $jobStatus;

    public $agent;

    public $batchId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($jobStatus, $agent, $batchId)
    {
        $this->jobStatus = $jobStatus;
        $this->agent = $agent;
        $this->batchId = $batchId;
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
