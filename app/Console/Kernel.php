<?php

namespace App\Console;

use App\Task;
use App\Jobs\ProcessTask;
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

        // Get all tasks from the database
        $tasks = Task::where('enabled', true)->get();

        // Go through each task to dynamically set them up.
        foreach ($tasks as $task) {

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
