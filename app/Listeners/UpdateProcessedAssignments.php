<?php

namespace App\Listeners;

use App\Agent;
use Exception;
use App\Assignment;
use Illuminate\Support\Str;
use App\Jobs\LogAssignments;
use App\Events\TicketsProcessed;
use App\Services\ZendeskService;
use Huddle\Zendesk\Facades\Zendesk;
use App\Events\AssignmentsProcessed;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateProcessedAssignments implements ShouldQueue
{
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
     * @param  TicketsProcessed  $event
     * @return void
     */
    public function handle(AssignmentsProcessed $event)
    {   
        $jobStatus = $event->jobStatus;
        $assignments = collect($event->assignments);
        $viewId = $event->viewId;
        
        while (true) {
            sleep(5);
            $response = app(\App\Services\ZendeskService::class)->getJobStatus($jobStatus->id);

            if ($response->job_status->status == "completed") {
                // $ticketResults = collect($response->job_status->results)->groupBy('id');
                dispatch_now(new LogAssignments($assignments));
                return;
            }

        }        
    }
}
