<?php

namespace App\Jobs\Assignments;

use App\Models\Agent;
use App\Models\Assignment;
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

    protected $failedResultDetails;

    protected $jobId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($batch, $successTicketIds, $failedResultDetails = [], $jobId = '')
    {
        $this->batch = $batch;
        $this->successTicketIds = $successTicketIds;
        $this->failedResultDetails = $failedResultDetails;
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AssignmentRepository $assignmentRepository, AgentRepository $agentRepository)
    {
        $assignments = $assignmentRepository->retrieveAssignments($this->batch);

        $processedAssignments = $assignments->reconcile($this->successTicketIds, $this->failedResultDetails, $this->jobId);
        
        $processedAssignments->updateLogs();

        $agentRepository->updateCurrentAssignmentLog($processedAssignments);
    }
}
