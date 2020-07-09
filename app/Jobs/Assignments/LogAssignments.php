<?php

namespace App\Jobs\Assignments;

use App\Agent;
use App\Assignment;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use App\Repositories\AgentRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Repositories\AssignmentRepository;
use App\Repositories\TicketRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class LogAssignments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batch;

    protected $successTicketIds;

    protected $failedTicketIds;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($batch, $successTicketIds, $failedTicketIds)
    {
        $this->batch = $batch;
        $this->successTicketIds = $successTicketIds;
        $this->failedTicketIds = $failedTicketIds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AssignmentRepository $assignmentRepository, AgentRepository $agentRepository)
    {
        logs()->debug($this->batch);
        logs()->debug("SUCCESS" . serialize($this->successTicketIds));
        logs()->debug("Fail" . serialize($this->failedTicketIds));

        $assignments = $assignmentRepository->getPrepared($this->batch);

        $processedAssignments = $assignments->reconcile($this->successTicketIds, $this->failedTicketIds);

        $processedAssignments->logs();
        $agentRepository->updateAssignment($assignments);
    }
}
