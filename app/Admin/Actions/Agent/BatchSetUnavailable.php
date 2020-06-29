<?php

namespace App\Admin\Actions\Agent;

use App\Agent;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchSetUnavailable extends BatchAction
{
    public $name = 'Set Unavailable';

    public function handle(Collection $collection)
    {
        $agents = Agent::where('status', true)->whereIn('id', $collection->pluck('id'))->get();

        $agents->each(function($agent) {
            $agent->status = false;
            $agent->save();
        });

        return $this->response()->success('Success setting unavailable ' . $agents->count() . ' agent(s)')->refresh();
    }

}