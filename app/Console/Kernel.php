<?php

namespace App\Console;

use App\Jobs\Assignments\AssignBatch;
use App\Jobs\Assignments\UnassignBatch;
use App\Repositories\AgentRepository;
use App\Repositories\TicketRepository;
use App\Task;
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


        $activeTasks->groupBy('interval')->each(function($tasks, $interval) use ($schedule) {
            $frequency = $interval;
            $schedule->call(function() use ($tasks) {
                AssignBatch::dispatch($tasks->values())->onQueue('assignment');
            })->$frequency();
        });

        $schedule->call(function() {
            $unassignEligibleAgents = app(AgentRepository::class)->getUnassignEligible();
        
            if ($unassignEligibleAgents->isNotEmpty()) {
                UnassignBatch::dispatch()->onQueue('unassignment');
            }
        })->everyMinute();


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
