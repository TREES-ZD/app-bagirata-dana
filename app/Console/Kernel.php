<?php

namespace App\Console;

use App\Task;
use App\Jobs\Task\ProcessTask;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();

        // Get all enabled tasks where from the database
        $activeTasks = Task::where('enabled', true)
                        ->withCount(['rules' => function($q) {
                            $q->where('rules.priority', '>', 0);
                            $q->where('agents.status', true);
                        }])
                        ->get()
                        ->filter(function($task) { return $task->rules_count > 0;});

        // Go through each task to dynamically set them up.
        foreach ($activeTasks as $task) {

            $frequency = $task->interval; // everyHour, everyMinute, twiceDaily etc.
            $schedule->call(function() use ($task) {
                ProcessTask::dispatch($task);
            })->$frequency();
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
