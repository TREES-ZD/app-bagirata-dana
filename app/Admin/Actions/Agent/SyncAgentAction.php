<?php

namespace App\Admin\Actions\Agent;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class SyncAgentAction extends BatchAction
{
    protected $id;

    protected $action;

    public function __construct($action = 1)
    {
        $this->action = $action;
    }
    
    public function script()
    {
        return <<<EOT
                
        $('.agent_resync').on('click', function() {
            $.ajax({
                method: 'post',
                url: '/backend/agents/sync',
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

        // return '<a class="agent_resync" href="javascript:void(0)"><i class="fa fa-refresh"></i> Sync</a>';
        return '<a href="/backend/agents/sync"><i class="fa fa-refresh"></i> Sync</a>';
    }    

    public function __toString()
    {
        return $this->render();
    }
}