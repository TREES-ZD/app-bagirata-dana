<?php

namespace Tests\Helper;

use Mockery;
use App\Task;
use App\Agent;
use Carbon\Carbon;
use App\Assignment;
use Tests\TestCase;
use App\Jobs\ProcessTask;
use Illuminate\Support\Str;
use Faker\Generator as Faker;
use App\Services\Zendesk\Ticket;
use App\Services\ZendeskService;
use Illuminate\Support\Facades\DB;
use App\Services\Zendesk\TicketCollection;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TicketFactory
{
    /**
     * Undocumented variable
     *
     * @var Ticket
     */
    protected $builder;

    protected $counter;

    public function __construct()
    {
        $this->counter = 1;
        $this->builder = $this->defaultTicket();
    }

    public function id(int $id): TicketFactory
    {
        $this->builder->ticket->id = $id;
        $this->builder->ticket->subject = sprintf("tiket %s", (string) $id);
        return $this;
    }

    public function unassigned(int $group_id = null): TicketFactory
    {
       $this->builder->ticket->group_id = $group_id;
       return $this;
    }

    public function assignedTo(int $group_id, int $assignee_id, string $custom_field): TicketFactory
    {
        $this->builder->ticket->group_id = $group_id;
        $this->builder->ticket->assignee_id = $assignee_id;
        $this->builder->ticket->custom_fields = [
            (object) ["id" => 123456, "value" => $custom_field]
        ];
        return $this;
    }

    public function make(array $attributes = []): Ticket
    {
        $newAttributes = array_merge((array) $this->builder->ticket, $attributes);
        $this->builder->ticket = (object) $newAttributes;
        return $this->builder;
    }

    private function defaultTicket()
    {
        $id = app(Faker::class)->randomDigitNotNull;
        return new Ticket((object) [
            "id" => $id,
            "subject" => sprintf("tiket %s", (string) $id),
            "status" => "open",
            "assignee_id" => null,
            "group_id" => null,
            "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]
        ]);

    }
}