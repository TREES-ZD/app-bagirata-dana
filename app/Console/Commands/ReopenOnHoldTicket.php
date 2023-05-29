<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\Assignment;
use Huddle\Zendesk\Facades\Zendesk;
use Illuminate\Console\Command;
use App\Repositories\AgentRepository;

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
        $page = 1;
        do {
            $onHoldTicketIds = collect(Zendesk::search()->find("type:ticket tags:bagirata_distributed status:hold", ["page" => $page])->results)->pluck('id');
            
            if ($onHoldTicketIds->isEmpty()) return $this->info('Nothing found');

            $this->info("Start Reopening tickets " . $onHoldTicketIds->join(', '));
            $params = [
                "ids" => $onHoldTicketIds->all(),
                "status" => "open",
                "remove_tags" => "bagirata_distributed"
            ];
            $response = Zendesk::tickets()->updateMany($params);
            $this->info('Done reopening tickets ' . $response->job_status->id);

        } while ($onHoldTicketIds->count() == 100 && $page < 10);

     
    }
}
