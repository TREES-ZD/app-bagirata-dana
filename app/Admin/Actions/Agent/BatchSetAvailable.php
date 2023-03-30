<?php

namespace App\Admin\Actions\Agent;

use App\Models\Agent;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchSetAvailable extends BatchAction
{
    public $name = 'Set Available';

    public function handle(Collection $collection)
    {
        $agents = Agent::where('status', false)->whereIn('id', $collection->pluck('id'))->get();

        $agents->each(function($agent) {
            $agent->status = true;
            $agent->save();
        });

        return $this->response()->success('Success setting available ' . $agents->count() . ' agent(s)')->refresh();
    }

}