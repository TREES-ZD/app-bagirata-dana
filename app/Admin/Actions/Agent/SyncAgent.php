<?php

namespace App\Admin\Actions\Agent;

use App\Jobs\SyncAgents;
use Illuminate\Http\Request;
use Encore\Admin\Actions\Action;

class SyncAgent extends Action
{
    protected $selector = '.sync-agent';

    public function handle(Request $request)
    {
        // $request ...
        SyncAgents::dispatchNow();
        return $this->response()->topCenter()->success('Success message...')->refresh();
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-default sync-agent"><i class="fa fa-refresh"></i> Sync</a>
HTML;
    }
}