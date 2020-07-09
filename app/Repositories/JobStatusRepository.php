<?php

namespace App\Repositories;

use App\Traits\RoundRobinable;
use App\Services\ZendeskService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Repositories\Traits\Batchable;
use App\Collections\AssignmentCollection;

class JobStatusRepository
{
    public function __construct(ZendeskService $zendesk)
    {
        $this->zendesk = $zendesk;
    }

    public function get($ids) 
    {
        return $this->zendesk->getJobStatuses($ids);
    }
}