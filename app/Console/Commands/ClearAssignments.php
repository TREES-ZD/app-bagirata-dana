<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\Assignment;
use Illuminate\Console\Command;
use App\Repositories\AgentRepository;

class ClearAssignments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assignments:clear {--monthago=3}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tobeDeletedAssignments = Assignment::where('created_at', '<', $date = now()->subMonths($this->option('monthago')));
        $this->info("assignments to be deleted before " . $date->format("d-M-Y") . " (" . $tobeDeletedAssignments->count() . ")");
        $this->info($tobeDeletedAssignments->delete() . " successfully deleted");
    }
}
