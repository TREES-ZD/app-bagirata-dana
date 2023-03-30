<?php

namespace App\Admin\Actions\Agent;

use App\Models\Agent;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchDelete extends BatchAction
{
    public $name = 'Delete';

    public function handle(Collection $collection)
    {
        Agent::whereIn('id', $collection->pluck('id'))->delete();

        return $this->response()->success('Success deleting ' . $collection->count() . ' agent(s)')->refresh();
    }

}