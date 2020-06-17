<?php

namespace App\Jobs;

use App\Agent;
use App\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class LogAssignments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $assignments;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($assignments)
    {
        $this->assignments = $assignments;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $assignments = $this->assignments
        ->each(function($assignment) {
            $agent = $assignment->get('agent');
            $ticket = $assignment->get('ticket');                    
            Redis::sadd(sprintf("agent:%s:assignedTickets", $agent->id), $ticket->id);
        })
        ->map(function($assignment, $i) {
            $agent = $assignment->get('agent');
            $ticket = $assignment->get('ticket');
            return [
                "type" => Agent::ASSIGNMENT,
                "batch_id" => "batch_id",
                "agent_id" => $agent->id,
                "agent_name" => $agent->fullName,
                "zendesk_view_id" => "viewId",
                "zendesk_ticket_id" => $ticket->id,
                "zendesk_ticket_subject" => $ticket->subject,
                "response_status" => 200,
                "created_at" => now()->addSeconds($i)
            ];
        });
        
        Assignment::insert($assignments->all());
    }
}
