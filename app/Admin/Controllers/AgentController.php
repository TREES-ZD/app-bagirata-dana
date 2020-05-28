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
use App\Services\ZendeskService;
use Encore\Admin\Layout\Content;
use App\Admin\Actions\MakeOnline;
use App\Http\Controllers\Controller;
use App\Admin\Actions\Agent\SyncAgent;
use App\Admin\Actions\Post\ImportPost;
use App\Admin\Actions\Agent\SyncAgentAction;
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
            $grid->fullName("Agent Full Name");
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

    public function create(Content $content, ZendeskService $zendesk) {
        $form = Admin::form(new Agent, function(Form $form) use ($zendesk) {            
            $form->display('id', 'ID');
            $form->footer(function ($footer) {
                $footer->disableReset();        
                // $footer->disableSubmit();    
                $footer->disableViewCheck();
                $footer->disableEditingCheck();
                $footer->disableCreatingCheck();    
            });

            $form->multipleSelect(ZendeskService::AGENT_IDS, 'Assignee')
                    ->options([ZendeskService::ALL => "All"] + (array) $zendesk->getUsersByKey(ZendeskService::ALL, ZendeskService::SHOW_NAME_ONLY));
            $form->multipleSelect(ZendeskService::GROUP_IDS, 'Group')
                    ->options([ZendeskService::ALL => "All"] + (array) $zendesk->getGroupsByKey(ZendeskService::ALL, ZendeskService::SHOW_NAME_ONLY));
            $form->multipleSelect(ZendeskService::CUSTOM_FIELD_IDS, 'Agent Name')
                    ->options([ZendeskService::ALL => "All"] + (array) $zendesk->getCustomFieldsByValue(ZendeskService::ALL, ZendeskService::SHOW_NAME_ONLY));
        });

        return $content->body($form);    
    }
    
    public function store(Request $request, ZendeskService $zendesk) {
        $filters = $request->only(ZendeskService::AGENT_IDS, ZendeskService::GROUP_IDS, ZendeskService::CUSTOM_FIELD_IDS);
        
        // Strip last element karena selalu default [..., x => null]
        $filters = collect($filters)->map(function($filter) {
            array_pop($filter);
            
            if (count($filter) < 1) {
                return null;
            }

            return $filter;
        })->toArray();
        
        SyncAgents::dispatchNow($filters);

        return redirect()->to('/admin/agents');
    }    
    
    public function edit(Content $content) {
        
        return Admin::form(Agent::findOrFail(2), function($form) {
            admin_toastr('Message...', 'success');

        });
    }

    public function update(Request $request, $id) {
        \Debugbar::info($request->all());
        \Debugbar::info($id);
        $agent = Agent::findOrFail($id);
        $agent->status = $request->get('status');
        $agent->save();
        // \Debugbar::info($agent);

        return $agent;
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
