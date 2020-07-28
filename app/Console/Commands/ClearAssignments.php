<?php

namespace App\Console\Commands;

use App\Agent;
use Illuminate\Console\Command;
use App\Repositories\AgentRepository;

class ClearAssignments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assignments:clear';

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
        app(AgentRepository::class)->clearCurrentAssignmentLog(Agent::all());
    }
}