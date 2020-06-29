<?php

namespace App\Jobs\Agent;

use App\Agent;
use App\Assignment;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use App\Services\ZendeskService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class LogUnassignments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batchId;

    protected $agent;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($batchId, $agent)
    {
        $this->batchId = $batchId;
        $this->agent = $agent;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $tickets = Cache::get(sprintf("tickets:%s", $this->batchId));

        $jobResults = Cache::get("jobResults:$this->batchId");
        
        $successTicketIds = collect($jobResults)->filter(function ($result) {
            return optional($result)->success;
        });

        Redis::srem(sprintf("agent:%s:assignedTickets", $this->agent->id), ...$successTicketIds->pluck('id')->all());

        $successTickets = $tickets->whereIn('id', $successTicketIds->pluck('id')->values()->all());

        $unassignments = $successTickets->map(function($ticket, $i) {
            $type = Str::upper("already_" . $ticket->status);

            if (in_array($ticket->status, ["new", "open", "pending"])) {
                $type = Agent::UNASSIGNMENT;
            }

            return [
                "type" => $type,
                "zendesk_view_id" => "TEMP_NO",
                "batch_id" => "TEMP_NO",
                "agent_id" => $this->agent->id,
                "agent_name" => $this->agent->fullName,
                "zendesk_ticket_id" => $ticket->id,
                "zendesk_ticket_subject" => $ticket->subject,
                "response_status" => 200,
                "created_at" => now()
            ];
        });

        Assignment::insert($unassignments->all());

    }
}
