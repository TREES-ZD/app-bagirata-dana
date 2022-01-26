<?php

namespace Tests\Integration\Jobs;

use Mockery;
use App\Task;
use App\Agent;
use Carbon\Carbon;
use App\Assignment;
use Tests\TestCase;
use App\Jobs\ProcessTask;
use Illuminate\Support\Str;
use App\Services\ZendeskService;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function test_latestAssignmentsPerViewId()
    {
        factory(Assignment::class)->createMany([
            ["id" => 1, "agent_id" => 1, "zendesk_view_id" => "view1"],
            ["id" => 2, "agent_id" => 2, "zendesk_view_id" => "view1"],
            ["id" => 3, "agent_id" => 1, "zendesk_view_id" => "view1"],
            ["id" => 4, "agent_id" => 2, "zendesk_view_id" => "view1"],
            ["id" => 5, "agent_id" => 1, "zendesk_view_id" => "view2"],
            ["id" => 6, "agent_id" => 2, "zendesk_view_id" => "view2"],
            ["id" => 7, "agent_id" => 1, "zendesk_view_id" => "view2"],
            ["id" => 8, "agent_id" => 2, "zendesk_view_id" => "view2"],
        ]);

        $assignments = Assignment::latestAssignmentsPerViewId([1, 2, 3]);
        
        $this->assertCount(2, $assignments->get(1));
        $this->assertCount(2, $assignments->get(2));
        $this->assertCount(0, $assignments->get(3));
     }
}