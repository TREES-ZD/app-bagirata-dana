<?php

namespace App\Admin\Controllers;

use App\Models\Agent;
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
use Encore\Admin\Actions\Toastr;
use Encore\Admin\Layout\Content;
use App\Admin\Actions\MakeOnline;
use App\Http\Controllers\Controller;
use App\Admin\Actions\Agent\SyncAgent;
use App\Admin\Actions\Post\ImportPost;
use Encore\Admin\Controllers\Dashboard;
use Illuminate\Support\Facades\Artisan;
use App\Admin\Actions\Agent\BatchDelete;
use App\Admin\Actions\Post\BatchReplicate;
use App\Admin\Actions\Agent\SyncAgentAction;
use App\Admin\Actions\Agent\BatchSetAvailable;
use App\Admin\Actions\Agent\BatchSetUnavailable;

class AgentController extends Controller
{
    private $zendesk;

    public function __construct(ZendeskService $zendesk)
    {
        $this->zendesk = $zendesk;
    }

    public function index(Content $content) {
        
        $grid = Admin::grid(new Agent, function (Grid $grid) {
            $grid->disableColumnSelector();
            $grid->disableExport();
            // $grid->disableFilter();
            // $grid->tools(function ($tools) {
            //     $tools->append(new SyncAgentAction());
            // });
            $grid->batchActions(function ($batch) {
                $batch->disableDelete();
                $batch->add(new BatchSetAvailable());
                $batch->add(new BatchSetUnavailable());

                if (Admin::user()->isAdministrator()) {
                    $batch->add(new BatchDelete());
                }
            });
            $grid->disableActions();

            if (!Admin::user()->isAdministrator()) {
                $grid->disableCreateButton();
            }
            
            $grid->model()->orderBy('zendesk_custom_field_name');
            $grid->filter(function($filter){
                // Remove the default id filter
                $filter->disableIdFilter();

                // Add a column filter
                $filter->ilike('zendesk_agent_name', 'Assignee');
                $filter->ilike('zendesk_group_name', 'Group');
                $filter->ilike('zendesk_custom_field_name', 'Agent Name');
                $filter->in('status', 'Availability')->radio([
                    '' => 'All',
                    true => 'Available',
                    false => 'Unavailable',
                ]);
                
            });            // $grid->expandFilter();

            $grid->paginate(20);
            $grid->fullName("Agent Full Name");
            $grid->zendesk_agent_name("Assignee")->sortable();
            $grid->zendesk_group_name("Group")->sortable();
            // $grid->fullName("Full Name");
            $grid->zendesk_custom_field_name("Agent Name")->sortable();
            // set text, color, and stored values
            $states = [
                'off' => ['value' => 0, 'text' => 'Off', 'color' => 'default'],
                'on' => ['value' => 1, 'text' => 'On', 'color' => 'primary'],
            ];
            $grid->status('Availability')->switch($states);
            // $grid->status("Availability")->select([
            //     true => 'Available',
            //     false => 'Unavailable',
            // ])->sortable();      
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

            $this->initializeUserScope();

            $assigneeOptions = $this->getAssigneeOptions();
            $groupOptions = $this->getGroupOptions();

            $form->multipleSelect(ZendeskService::AGENT_IDS, 'Assignee')
                    ->options($assigneeOptions);
            $form->multipleSelect(ZendeskService::GROUP_IDS, 'Group')
                    ->options($groupOptions);
            $form->multipleSelect(ZendeskService::CUSTOM_FIELD_IDS, 'Agent Name')
                    ->options([ZendeskService::ALL => "All"] + $zendesk->getCustomFieldsByValue(ZendeskService::ALL, ZendeskService::SHOW_NAME_ONLY)->toArray());
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

        return redirect()->to('/backend/agents');
    }    
    
    public function edit(Content $content) {
        
        return Admin::form(Agent::findOrFail(2), function($form) {
            admin_toastr('Message...', 'success');

        });
    }

    public function update(Request $request, $id) {
        $agent = Agent::where('id', $id)->firstOrFail();
        
        // Workaround to check same status can't be updated with the same status
        if ($agent->status != $request->get('status')) {
            $agent->status = $request->get('status');
            $agent->save();
            return response()->json(["status" => "Sucess updating status"], 200);
        }
 
        return response()->json(["status" => "bad"], 400);
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

    private function getAssigneeOptions() {
        return $this->zendesk->getUsersByKey(ZendeskService::ALL, ZendeskService::SHOW_NAME_ONLY)->all();
    }

    private function getGroupOptions() {
        return $this->zendesk->getGroupsByKey(ZendeskService::ALL, ZendeskService::SHOW_NAME_ONLY)->all();    
    }

    private function initializeUserScope() {
        $user = Admin::user();

        if ($user->isAdministrator()) {
            return;
        }

        $assignee_ids = json_decode($user->zendesk_assignee_ids);
        if ($assignee_ids) {
            $this->zendesk->filterUsers($assignee_ids);
        }

        $group_ids = json_decode($user->zendesk_group_ids);
        if ($group_ids) {
            $this->zendesk->filterGroups($group_ids);
        }
    }
}
