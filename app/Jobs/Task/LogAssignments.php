<?php

namespace App\Jobs\Task;

use App\Agent;
use App\Assignment;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class LogAssignments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batchId;

    protected $page;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($batchId, $page)
    {
        $this->batchId = $batchId;
        $this->page = $page;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {        
        $assignments = collect(Cache::get(sprintf("assignments:%s", $this->batchId)))->chunk(100)->get($this->page)->values();

        $assignments->each(function($assignment) {
            Redis::sadd(sprintf("agent:%s:assignedTickets", $assignment->agent_id), $assignment->ticket_id);
        });

        $assignments = $assignments->map(function($assignment, $i) {
                            return [
                                "type" => Agent::ASSIGNMENT,
                                "batch_id" => $this->batchId,
                                "agent_id" => $assignment->agent_id,
                                "agent_name" => $assignment->agent_fullName,
                                "zendesk_view_id" => "viewId",
                                "zendesk_ticket_id" => $assignment->ticket_id,
                                "zendesk_ticket_subject" => $assignment->ticket_subject,
                                "response_status" => 200,
                                "created_at" => now()->addSeconds($i)
                            ];
                        });
        Assignment::insert($assignments->all());
    }
}
