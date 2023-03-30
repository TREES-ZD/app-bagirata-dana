<?php

namespace App\Admin\Actions\Task;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class SyncAgentsAction extends BatchAction
{
    protected $id;

    protected $action;

    protected $selector = '.agents-sync';

    public function __construct($action = 1)
    {
        $this->action = $action;
    }

    public function script()
    {
        return <<<EOT
                
        $('.agents-sync').on('click', function() {
            $.ajax({
                method: 'post',
                url: '/backend/agents/syncAll',
                data: {
                    _token:LA.token
                },
                success: function (data) {
                    console.log(data);

                    $.pjax.reload('#pjax-container');
                    toastr.success(data.message);
                }
            });
        });

        EOT;

    }

    public function render()
    {
        \Encore\Admin\Facades\Admin::script($this->script());
        return '<div class="btn-group pull-right grid-create-btn" style="margin-right: 10px">
        <a class="btn btn-sm btn-primary agents-sync" title="Sync">
            <i class="fa fa-refresh"></i><span class="hidden-xs">&nbsp;&nbsp;Sync</span>
        </a>
        </div>';
        // return "<a class='sync-agents btn btn-sm btn-primary'>Sync <i class='fa fa-refresh'></i></a>";
        // return '<a class="agent_resync" href="javascript:void(0)"><i class="fa fa-refresh"></i> Sync</a>';
        // return '<a href="/backend/agents/sync"><i class="fa fa-refresh"></i> Quick Sync</a>';
    }    

    public function __toString()
    {
        return $this->render();
    }
}