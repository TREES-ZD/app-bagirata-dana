<?php

namespace Tests\Integration\Jobs;

use Mockery;
use App\Task;
use App\Agent;
use App\Assignment;
use Tests\TestCase;
use App\Jobs\ProcessTask;
use App\Services\ZendeskService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProcessTaskTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->task = factory(Task::class)->create();
        $this->agent = factory(Agent::class)->create();
        $this->agent->rules()->attach($this->task->id, array('priority' => 1));
        $this->job = new ProcessTask($this->task);
    }

    public function test_only_available_agents_get_assigned_tickets()
    {
        $agent = factory(Agent::class)->create([
            "status" => false
        ]);
        $agent->rules()->attach($this->task->id, ['priority' => 1]);
        
        $zendesk = Mockery::mock(ZendeskService::class);
        $zendesk->shouldReceive('getTicketsByView')
                ->andReturn(collect([
                    (object) ['id' => 1, "subject" => "subject_a", "group_id" => null],
                    (object) ['id' => 2, "subject" => "subject_b", "group_id" => null]
                ]));
        $zendesk->shouldReceive('updateTicket')
                ->twice();

        $this->job->handle($zendesk);

        $this->assertDatabaseHas('assignments', [
            "type" => "ASSIGNMENT",
            "agent_id" => $this->agent->id,
            "zendesk_ticket_id" => 1
        ]);
        $this->assertDatabaseHas('assignments', [
            "type" => "ASSIGNMENT",
            "agent_id" => $this->agent->id,
            "zendesk_ticket_id" => 2
        ]);        
        $this->assertDatabaseMissing('assignments', [
            'agent_id' => $agent->id
        ]);
    }

    public function test_agents_who_last_get_assigned_will_get_assigned_first()
    {
        // $agents = factory(Agent::class, 3)
        //         ->create()
        //         ->each(function ($agent, $key) {
        //             $agent->assignments()->create([
        //                 "type" => Agent::ASSIGNMENT,
        //                 "zendesk_view_id" => "123324",
        //                 "batch_id" => "aabc123",
        //                 "agent_name" => $agent->fullName,
        //                 "zendesk_ticket_id" => $key,
        //                 "zendesk_ticket_subject" => "Test",
        //                 "group_id" => $agent->zendesk_group_id,
        //                 "response_status" => 200,
        //                 "created_at" => Carbon::createFromTime($key, 0, 0)
        //             ]);
        //         });        

        // $zendesk = Mockery::mock(ZendeskService::class);
        // $zendesk->shouldReceive('getTicketsByView')
        //         ->andReturn([
        //              (object) ['id' => 1, "subject" => "subject_a", "group_id" => null],
        //              (object) ['id' => 2, "subject" => "subject_b", "group_id" => null],
        //              (object) ['id' => 3, "subject" => "subject_c", "group_id" => null]
        //          ]);

        // $zendesk->shouldReceive('updateTicket')
        //         ->with(Mockery::any(), Mockery::on(function($argument) use ($agents) {
        //             return $argument['assignee_id'] == $agents->get(0)->id;
        //         }))
        //         ->andReturn(true);
        // $zendesk->shouldReceive('updateTicket')
        //         ->with(Mockery::any(), Mockery::on(function($argument) use ($agents) {
        //             return $argument['assignee_id'] == $agents->get(1)->id;
        //         }))
        //         ->andReturn(true); 
        // $zendesk->shouldReceive('updateTicket')
        //         ->with(Mockery::any(), Mockery::on(function($argument) use ($agents) {
        //             return $argument['assignee_id'] == $agents->get(2)->id;
        //         }))
        //         ->andReturn(true);

        // $this->job->handle($zendesk);
    }

    // public function test_agents_who_havent_get_assigned_will_get_assigned_first()
    // {

    // }

    // public function test_agent_not_in_rule_will_not_get_assigned()
    // {

    // }

    // public function test_agents_will_get_the_same_amount_of_tickets() {

    // }

    // public function test_tickets_with_the_same_group_will_only_get_assigned_to_agents_on_that_group() {

    // }
}
