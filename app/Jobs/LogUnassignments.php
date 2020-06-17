<?php

namespace App\Jobs;

use App\Agent;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class LogUnassignments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $agent;

    protected $tickets;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($agent, $tickets)
    {
        $this->agent = $agent;
        $this->tickets = $tickets;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $tickets = collect($this->tickets)->filter(function($ticket) {
            return is_object($ticket);
        })->all();

        foreach ($tickets as $i => $ticket) {
            $type = Str::upper("already_" . $ticket->status);

            if (in_array($ticket->status, ["new", "open", "pending"])) {
                $type = Agent::UNASSIGNMENT;
            }

            if (Redis::srem(sprintf("agent:%s:assignedTickets", $this->agent->id), $ticket->id)) {
                $this->agent->assignments()->create([
                    "type" => $type,
                    "zendesk_view_id" => "TEMP_NO",
                    "batch_id" => "TEMP_NO",
                    "agent_id" => $this->agent->id,
                    "agent_name" => $this->agent->fullName,
                    "zendesk_ticket_id" => $ticket->id,
                    "zendesk_ticket_subject" => $ticket->subject,
                    "group_id" => $this->agent->zendesk_group_id,
                    "response_status" => 200
                ]);
            }
        }

    }
}
