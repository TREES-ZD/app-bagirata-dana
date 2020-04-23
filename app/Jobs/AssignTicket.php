<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Zendesk\API\HttpClient as ZendeskAPI;
use Illuminate\Support\Facades\Redis;

class AssignTicket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $agent_id;

    protected $ticket_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($agent_id, $ticket_id)
    {
        $this->agent_id = $agent_id;
        $this->ticket_id = $ticket_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $client = new ZendeskAPI(env("ZENDESK_SUBDOMAIN","contreesdemo11557827937"));
        $client->setAuth('basic', ['username' => "eldien.hasmanto@treessolutions.com", 'token' => "wZX70pAKu3aNyqOEYONUdjVLCIaoBMRFXjnbi7SE"]);

        Redis::funnel('update')->limit(1)->then(function() use ($client) {
            \Log::info("Start process " . (string) $this->ticket_id);
            sleep(10);
            $response = $client->tickets()->update($this->ticket_id, [
                'assignee_id' => $this->agent_id,
                'group_id' => 360000974835,
            ]);

            \Log::info("Update ticket: " . (string) $this->ticket_id);
            \Log::info("Response: " . json_encode($response));
        }, function() {
            return $this->delete();
        });
    }
}
