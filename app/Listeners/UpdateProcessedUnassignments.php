<?php

namespace App\Listeners;

use App\Jobs\LogUnassignments;
use App\Events\UnassignmentsProcessed;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateProcessedUnassignments implements ShouldQueue
{

    public $queue = 'unassignment';
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UnassignmentsProcessed  $event
     * @return void
     */
    public function handle(UnassignmentsProcessed $event)
    {
        $jobStatus = $event->jobStatus;
        $agent = $event->agent;
        $batchId = $event->batchId;

        if (!$jobStatus) {
            dispatch_now(new LogUnassignments($agent, $batchId));
            return;
        }

        while (true) {
            sleep(5);
            $response = app(\App\Services\ZendeskService::class)->getJobStatus($jobStatus->id);

            if ($response->job_status->status == "completed") {
                dispatch_now(new LogUnassignments($agent, $batchId));
                return;
            }

        }        
    }

}
