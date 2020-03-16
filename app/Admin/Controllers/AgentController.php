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
            // $grid->actions(function ($actions) {
            //     // $actions->disableDelete();
            //     // $actions->disableEdit();
            //     // $actions->disableView();
            //     $actions->disableAll();
            // });       
            $grid->disableActions();  
            
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
                "Online" => ['value' => "Available", 'text' => 'online', 'color' => 'primary'],
                "Offline" => ['value' => "Away", 'text' => 'offline', 'color' => 'default'],
            ];

            $reassignStates = [
                'on' => ['value' => true, 'text' => 'on', 'color' => 'primary'],
                'off' => ['value' => false, 'text' => 'off', 'color' => 'default'],
            ];            

            $grid->agent_id("Zendesk ID");
            $grid->agent_name("Agent");
            $grid->group_name("Group");
            // $grid->fullName("Full Name");
            $grid->custom_field("custom_field:agent_name");
            $grid->status("Available")->select([
                true => 'Avail',
                false => 'None',
            ]);      
            // $grid->status("Availability")->editable()->switch($states);
            $grid->limit("Limit");   
            // $grid->column("Reassign")->editable()->switch($reassignStates);
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

    public function update(Content $content, Request $request, $id) {
        \Debugbar::info($request->all());
        \Debugbar::info($id);
        $agent = Agent::findOrFail($id);
        $agent->status = $request->get('status');
        $agent->save();
        \Debugbar::info($agent);

        return "CARALHO";
    }    
    
    public function destroy() {

    }    
}
