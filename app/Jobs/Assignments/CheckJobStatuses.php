<?php

namespace App\Jobs\Assignments;

use App\Services\Zendesk\JobStatus;
use Illuminate\Bus\Queueable;
use App\Services\ZendeskService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Repositories\TicketRepository;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Repositories\JobStatusRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Laravel\Horizon\Contracts\JobRepository;

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
    public function handle(JobStatusRepository $jobRepository)
    {        
        sleep(5);

        $jobStatuses = $jobRepository->get($this->jobStatusIds);
        while (1) {
            if ($jobStatuses->areAllCompleted()) {
                $jobStatuses->each(function(JobStatus $jobStatus) {
                    LogAssignments::dispatch($this->batch, $jobStatus->successTicketIds()->all(), $jobStatus->failedResultDetails()->all(), $jobStatus->id())->onQueue($this->queue);
                });

                return;
            }

            sleep(10);

            $jobStatuses->fresh();
        }    
    }

    public function failed() {

    }
}
