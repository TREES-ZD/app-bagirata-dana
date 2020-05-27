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

        $existingTasksByViewId = Task::all()->keyBy(function($task) {
            // Identify agent based on the pattern ':zendesk_agent_id-:zendesk_group_id-:zendesk_custom_field_id' 
            return $task->zendesk_view_id;
        });

        $views = $views->map(function($view) use ($existingTasksByViewId) {

            $existingTask = $existingTasksByViewId->get($view->id);

            return [
                'zendesk_view_id' => $view->id,
                'zendesk_view_title' => $view->title,
                'interval' => "everyMinute",
                'group_id' => 1,
                'limit' => "unlimited",
                'enabled' => $existingTask['enabled'] ?: false
            ];
        });

        DB::transaction(function() use ($views) {
            Task::truncate();                
            Task::insert($views->toArray());
        });

        Artisan::call('modelCache:clear',['--model' => Task::class]);
    }
}
