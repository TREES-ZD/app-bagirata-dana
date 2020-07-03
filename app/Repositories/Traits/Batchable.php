<?php

namespace App\Repositories\Traits;

use App\Traits\RoundRobinable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use App\Collections\AssignmentCollection;

trait Batchable
{
    public function cache($batch, Collection $collection = null) {
        $cacheName = $this->cacheName($batch);

        return Cache::remember($cacheName, 3000, function() use ($collection) {
            return $collection;
        });
    }

    public function batch($batchId, Collection $collection = null) {
        $cacheName = $this->cacheName($batchId);

        if (!$collection) return Cache::get($cacheName);

        Cache::forget($cacheName);
        return Cache::remember($cacheName, 3000, function() use ($collection) {
            return $collection;
        });
    }

    public function getByPagedBatch($pagedBatch) {
        $cacheName = $this->cacheName($this->batchId($pagedBatch));
        $collection = Cache::get($cacheName);

        return $collection->chunk(100)->get($this->batchPage($pagedBatch))->values();
    }
    
    private function batchId($pagedBatch) {
        return explode(":", $pagedBatch)[0];
    }

    private function batchPage($pagedBatch) {
        return explode(":", $pagedBatch)[1];
    }

    private function cacheName($batchId) {
        return sprintf("$this->cachePrefix:%s", $batchId);
    }
}