<?php

namespace Tests\Helper;

use Mockery;
use App\Task;
use App\Agent;
use Carbon\Carbon;
use App\Assignment;
use Tests\TestCase;
use App\Jobs\ProcessTask;
use App\Services\Zendesk\JobStatus;
use Illuminate\Support\Str;
use Faker\Generator as Faker;
use App\Services\Zendesk\Ticket;
use App\Services\ZendeskService;
use Illuminate\Support\Facades\DB;
use App\Services\Zendesk\TicketCollection;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JobStatusFactory
{
    /**
     * Undocumented variable
     *
     * @var JobStatus
     */
    protected $builder;

    protected $counter;

    public function __construct()
    {
        $this->counter = 1;
        $this->builder = $this->defaultJobStatus();
    }

    public function id(string $id): JobStatusFactory
    {
        $this->builder->jobStatus->id = $id;
        return $this;
    }

    public function completed(array $ids): JobStatusFactory
    {
        $results = collect($ids)->map(function($id) {
            return (object) ["action" => "update", "id" => $id, "status" => "Updated", "success" => true];
        });

        $this->builder->jobStatus->results = $results->all();
        $this->builder->jobStatus->status = "completed";
        $this->builder->jobStatus->progress = $results->count();
        $this->builder->jobStatus->total = $results->count();

        return $this;
    }

    public function queued(array $ids): JobStatusFactory
    {
        $results = collect($ids)->map(function($id) {
            return (object) ["action" => "update", "id" => $id, "status" => "Updated", "success" => true];
        });

        $this->builder->jobStatus->results = $results->all();
        $this->builder->jobStatus->status = "queued";
        $this->builder->jobStatus->progress = $results->count();
        $this->builder->jobStatus->total = $results->count();

        return $this;
    }

    public function make(array $attributes = []): JobStatus
    {
        $newAttributes = array_merge((array) $this->builder->jobStatus, $attributes);
        $this->builder->jobStatus = (object) $newAttributes;
        return $this->builder;
    }

    private function defaultJobStatus(): JobStatus
    {
        $id = app(Faker::class)->uuid;
        return new JobStatus((object) [
            "id" => $id,
            "progress" => 1,
            "results" => [
                (object) ["action" => "update", "id" => 100, "status" => "Updated", "success" => true]
            ],
            "status" => "queued",
            "total" => 1
        ]);

    }
}