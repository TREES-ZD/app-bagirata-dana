<?php

namespace App\Jobs\Assignments;

use Illuminate\Bus\Queueable;
use App\Services\Zendesk\JobStatus;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\Zendesk\JobStatusService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckJobStatuses implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200;

    public $batch;
    public $jobStatusIds;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($batch, $jobStatusIds)
    {
        $this->batch = $batch;
        $this->jobStatusIds = $jobStatusIds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(JobStatusService $jobStatusService)
    {        
        sleep(5);

        $jobStatuses = $jobStatusService->check($this->jobStatusIds);
        while (1) {
            if ($jobStatuses->areAllCompleted()) {
                $jobStatuses->each(function(JobStatus $jobStatus) {
                    UpdateLogs::dispatch($this->batch, $jobStatus->successTicketIds()->all(), $jobStatus->failedTicketIds()->all())->onQueue($this->queue);
                });

                return;
            }

            sleep(10);

            $jobStatuses->refresh();
        }    
    }

    public function failed() {

    }
}
