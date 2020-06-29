<?php

namespace App\Jobs;

use App\Services\ZendeskService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckJobStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $batchId;

    public $jobStatusId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($batchId, $jobStatusId)
    {
        $this->batchId = $batchId;
        $this->jobStatusId = $jobStatusId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ZendeskService $zendesk)
    {
        sleep(5);
        while (true) {
            $response = $zendesk->getJobStatus($this->jobStatusId);

            if ($response->job_status->status == "completed") {
                Cache::remember("jobResults:$this->batchId", 3000, function() use ($response) {
                    return $response->job_status->results;
                });

                return;
            }

            sleep(10);
        }    
    }

    public function failed() {
        Cache::restoreLock('updateTickets', $this->owner)->forceRelease();
    }
}
