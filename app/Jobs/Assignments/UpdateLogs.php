<?php

namespace App\Jobs\Assignments;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\Assignments\AssignmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateLogs implements ShouldQueue
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
    public function handle(AssignmentService $assignmentService)
    {
        $assignmentService->updateLogs($this->batch, $this->successTicketIds, $this->failedTicketIds);
    }
}
