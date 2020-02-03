<?php

namespace App\Admin\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Table;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use App\Agent;
use App\Admin\Actions\MakeOnline;
use App\Admin\Actions\Post\BatchReplicate;
use App\Admin\Actions\Post\ImportPost;

class AgentController extends Controller
{
    public function index(Content $content) {
        $grid = Admin::grid(new Agent, function (Grid $grid) {
            // $grid->disableTools();
            $grid->disableColumnSelector();

            // $grid->tools(function (Grid\Tools $tools) {
            //     // $tools->append(new BatchReplicate());
            //     // $tools->append(new ImportPost());
            // });
            $grid->actions(function ($actions) {
                // $actions->disableDelete();
                // $actions->disableEdit();
                // $actions->disableView();
            });         
            
            $grid->filter(function ($filter) {
                // $filter->like('agent_name');
                $filter->column(1/2, function ($filter) {
                    $filter->like('agent_name');
                    $filter->like('group_name');
                    $filter->equal('status')->radio([
                        1 => 'online',
                        0 => 'offline',
                    ]);

                });              
            });
            

            $grid->sortable();
            $states = [
                'on' => ['value' => true, 'text' => 'online', 'color' => 'primary'],
                'off' => ['value' => false, 'text' => 'offline', 'color' => 'default'],
            ];

            $grid->priority();
            $grid->status()->editable()->switch($states);
            $grid->agent_name("Agent")->modal('latest tickets', function() {
                return "HALO";
            });
            $grid->group_name("Group");
            $grid->custom_field("custom_field:agent_name");
            $grid->limit("Limit");   

        });

        return $content->body($grid);
    }

    public function show(Content $content) {
        $show = Admin::show(Agent::findOrFail(1), function($show) {
            $show->agent_name()->as(function ($title) {
                return "<{$title}>";
            });
            $show->rate()->badge();
            $show->group_id()->using([10 => 'sepuluh', 11 => 'sebelas']);

        });

        return $content->body($show);
    }  

    public function create() {
        
    }
    
    public function store() {
        
    }    
    
    public function edit(Content $content) {
        
        return Admin::form(Agent::findOrFail(2), function($form) {
            admin_toastr('Message...', 'success');

        });
    }

    public function update(Content $content) {
        
    }    
    
    public function destroy() {

    }    
}
