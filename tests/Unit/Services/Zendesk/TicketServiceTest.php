<?php

namespace Tests\Unit\Services\Zendesk;

use App\Task;
use Tests\TestCase;
use App\Traits\RoundRobinable;
use App\Services\Zendesk\Ticket;
use Illuminate\Support\Collection;
use App\Collections\AgentCollection;
use App\Services\Zendesk\TicketService;
use App\Services\Zendesk\ZendeskWrapper;
use App\Services\Zendesk\TicketCollection;
use Tests\Helper\Seeder\AgentCollectionSeeder;
use Tests\Helper\Seeder\TicketCollectionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TicketServiceTest extends TestCase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function setUp() : void {
        parent::setUp();
        putenv("ZENDESK_AGENT_NAMES_FIELD=123456");
    }

    /**
     * @group migratedTest
     */
    public function test_tidakAkanMengassignTicketYangSudahTerassign() 
    {
        $this->markTestIncomplete();
    }

}