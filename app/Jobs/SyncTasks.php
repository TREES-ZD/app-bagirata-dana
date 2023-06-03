<?php

namespace App\Jobs;

use App\Services\ZendeskService;
use App\Models\Task;
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

        $existingTasks = Task::whereIn('zendesk_view_id', $views->pluck('id')->toArray())->get()->keyBy('zendesk_view_id');
    
        $views = $views
                ->map(function($view) use ($existingTasks) {
                    $existingTask = $existingTasks->get($view->id);
                    return [
                        'id' => optional($existingTask)->id,
                        'zendesk_view_id' => $view->id,
                        'zendesk_view_title' => $view->title,
                        'zendesk_view_position' => $view->position,
                        'interval' => "everyMinute",
                        'group_id' => 1,
                        'limit' => "unlimited",
                        'enabled' => !empty($existingTask) ? $existingTask->enabled : false
                    ];
                });

        DB::transaction(function() use ($views) {
            Task::upsert($views->whereNotNull('id')->toArray(), 'id');
            Task::insert($views->whereNull('id')->toArray());
        });

        Artisan::call('modelCache:clear',['--model' => Task::class]);
    }
}
