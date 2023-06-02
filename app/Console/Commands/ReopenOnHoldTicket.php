<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\Assignment;
use Huddle\Zendesk\Facades\Zendesk;
use Illuminate\Console\Command;
use App\Repositories\AgentRepository;
use Illuminate\Support\Facades\Redis;

class ReopenOnHoldTicket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trigger:reopenonhold';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Specific Jago Untuk mengakali In Queue agar tidak bisa diklik atau mereassign tiket dari messaging oleh agent';

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
        $onHoldTicketIds = collect(Redis::smembers('ids'));
        $this->info("Start Reopening tickets " . $onHoldTicketIds->join(', '));
        $params = [
            "ids" => $onHoldTicketIds->all(),
            "additional_tags" => ["bagirata_reopen"]
        ];
        $response = Zendesk::tickets()->updateMany($params);
        $this->info('Done reopening tickets ' . $response->job_status->id);
        Redis::del(['ids']);
    }
}
