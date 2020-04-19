<?php

namespace App\Admin\Controllers;

use App\Agent;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use App\Jobs\SyncAgents;
use Encore\Admin\Layout\Row;
use Illuminate\Http\Request;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Widgets\Table;
use Encore\Admin\Layout\Content;
use App\Admin\Actions\MakeOnline;
use App\Http\Controllers\Controller;
use App\Admin\Actions\Agent\SyncAgent;
use App\Admin\Actions\Post\ImportPost;
use App\Admin\Actions\SyncAgentAction;
use Encore\Admin\Controllers\Dashboard;
use Illuminate\Support\Facades\Artisan;
use App\Admin\Actions\Agent\BatchDelete;
use App\Admin\Actions\Post\BatchReplicate;

class AgentController extends Controller
{
    public function index(Content $content) {
        $grid = Admin::grid(new Agent, function (Grid $grid) {
            $grid->disableColumnSelector();
            $grid->disableExport();
            // $grid->disableCreateButton();
            $grid->disableFilter();
            $grid->tools(function ($tools) {
                $tools->append(new SyncAgentAction());
            });
            $grid->batchActions(function ($batch) {
                $batch->disableDelete();
                $batch->add(new BatchDelete());
            });
            
            $grid->paginate(10);
            $grid->sortable();
            $grid->zendesk_agent_name("Assignee")->sortable();
            $grid->zendesk_group_name("Group")->sortable();
            // $grid->fullName("Full Name");
            $grid->zendesk_custom_field_name("Agent Name")->sortable();
            $grid->status("Availability")->select([
                true => 'Available',
                false => 'Unavailable',
            ])->sortable();      
            // $grid->reassign("Reassignable")->select([
            //     true => 'Yes',
            //     false => 'No',
            // ]);              
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

    public function create(Content $content) {
        $form = Admin::form(new Agent, function(Form $form) {            
            $form->display('id', 'ID');
            $form->footer(function ($footer) {
                $footer->disableReset();        
                // $footer->disableSubmit();    
                $footer->disableViewCheck();
                $footer->disableEditingCheck();
                $footer->disableCreatingCheck();    
            });

            $form->select('zendesk_agent_id', 'Assignee')->options([
                "1234" => 'foo',
                "223" => 'bar',
            ]);
            $form->select('zendesk_group_id', 'Group')->options([
                360000974835 => 'BPO 2',
                360000974836 => 'Support',
            ]);
            $form->select('zendesk_custom_field_id', 'Agent Name')->options([
                1 => 'foo',
                2 => 'bar',
            ]);
        });

        return $content->body($form);    
    }
    
    public function store(Request $request) {
        $agent = new Agent();
        
        $agent->id = "unique1";
        $agent->priority =  1;
        $agent->zendesk_agent_id =  $request->zendesk_agent_id;
        $agent->zendesk_agent_name =  "tes1";
        $agent->zendesk_group_id =  $request->zendesk_group_id;
        $agent->zendesk_group_name =  "tes1";
        $agent->zendesk_custom_field_id =  $request->zendesk_custom_field_id;
        $agent->zendesk_custom_field_name =  "tes1";
        $agent->limit =  "unlimited";
        $agent->status = false;
        $agent->reassign = false;
        
        $agent->save();

        return redirect()->to('/admin/agents');
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
        SyncAgents::dispatchNow([
            "zendesk_assignee_id" => Admin::user()->zendesk_assignee_id,
            "zendesk_group_id" => Admin::user()->zendesk_group_id
            ]);
        return redirect()->back();
    }
}
