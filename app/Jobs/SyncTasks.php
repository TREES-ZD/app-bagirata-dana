<?php

namespace App\Jobs;

use App\Services\ZendeskService;
use App\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SyncTasks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ZendeskService $zendesk)
    {
        $views = collect($zendesk->getViews());

        $views = $views
                ->whereNotIn('id', Task::all()->pluck('zendesk_view_id')->all())
                ->map(function($view) {
                    return [
                        'zendesk_view_id' => $view->id,
                        'zendesk_view_title' => $view->title,
                        'zendesk_view_position' => $view->position,
                        'interval' => "everyMinute",
                        'group_id' => 1,
                        'limit' => "unlimited",
                        'enabled' => false
                    ];
                });

        DB::transaction(function() use ($views) {
            Task::insert($views->toArray());
        });

        Artisan::call('modelCache:clear',['--model' => Task::class]);
    }
}
