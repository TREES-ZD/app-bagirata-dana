<?php

namespace App\Admin\Controllers;

use App\Agent;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Row;
use Illuminate\Http\Request;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Widgets\Table;
use Encore\Admin\Layout\Content;
use App\Admin\Actions\MakeOnline;
use App\Http\Controllers\Controller;
use App\Admin\Actions\Post\ImportPost;
use App\Admin\Actions\SyncAgentAction;
use Encore\Admin\Controllers\Dashboard;
use App\Admin\Actions\Agent\BatchDelete;
use App\Admin\Actions\Post\BatchReplicate;

class AgentController extends Controller
{
    public function index(Content $content) {
        $grid = Admin::grid(new Agent, function (Grid $grid) {
            $grid->disableColumnSelector();
            $grid->disableExport();
            $grid->disableCreateButton();
            $grid->tools(function ($tools) {
                $tools->append(new SyncAgentAction());
            });
            $grid->batchActions(function ($batch) {
                $batch->disableDelete();
                $batch->add(new BatchDelete());
            });
            
            $grid->sortable();
            $grid->zendesk_agent_name("Agent")->sortable();
            $grid->zendesk_group_name("Group")->sortable();
            // $grid->fullName("Full Name");
            $grid->zendesk_custom_field_name("Custom Field")->sortable();
            $grid->status("Availability")->select([
                true => 'Available',
                false => 'Unavailable',
            ])->sortable();      
            $grid->reassign("Reassignable")->select([
                true => 'Yes',
                false => 'No',
            ]);              
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

    public function sync() {
        \App\Jobs\SyncAgents::dispatchNow();
        return redirect()->back();
    }
}
