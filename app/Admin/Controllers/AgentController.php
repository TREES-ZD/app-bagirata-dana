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
use App\Admin\Displayers\SimpleSelect;
use App\Admin\Actions\Agent\BatchDelete;
use App\Admin\Actions\Post\BatchReplicate;
use App\Admin\Actions\Agent\SyncAgentAction;
use App\Admin\Actions\Agent\BatchSetAvailable;
use App\Admin\Actions\Agent\BatchSetUnavailable;
use App\Admin\Actions\Task\SyncAgentsAction;
use App\Jobs\SyncAllAgents;
use Illuminate\Validation\Rule;

class AgentController extends Controller
{
    private $zendesk;

    public function __construct(ZendeskService $zendesk)
    {
        $this->zendesk = $zendesk;
    }

    public function index(Content $content) {
        
        $grid = Admin::grid(new Agent, function (Grid $grid) {
            if (request()->has('__search__')) {
                $input = request('__search__');
                $grid->model()->where('zendesk_agent_name', 'ilike', "%{$input}%")
                        ->orWhere('zendesk_group_name', 'ilike', "%{$input}%")
                        ->orWhere('zendesk_custom_field_name', 'ilike', "%{$input}%");
            }

            $grid->model()->orderBy('id');
            $grid->disableColumnSelector();
            $grid->disableExport();
            // $grid->disableFilter();
            $grid->with([
                'customActions' => collect([new SyncAgentsAction()])
            ]);
            $grid->batchActions(function ($batch) {
                $batch->disableDelete();
                // $batch->add(new BatchSetAvailable());
                // $batch->add(new BatchSetUnavailable());

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
                $filter->equal('custom_status', 'Availability')->select( [
                    Agent::CUSTOM_STATUS_UNAVAILABLE => '🔴 Unavailable',
                    Agent::CUSTOM_STATUS_AWAY => '🕘 Away',
                    Agent::CUSTOM_STATUS_AVAILABLE => '🟢 Available',
                ]);
                
            });            // $grid->expandFilter();
            $grid->quickSearch();

            $grid->paginate(20);
            $grid->fullName("Agent Full Name");
            $grid->zendesk_agent_name("Assignee")->sortable();
            $grid->zendesk_group_name("Group")->sortable();
            // $grid->fullName("Full Name");
            $grid->zendesk_custom_field_name("Agent Name")->sortable();
            // set text, color, and stored values
            $grid->custom_status("Availability")->displayUsing(SimpleSelect::class, [ [
                Agent::CUSTOM_STATUS_UNAVAILABLE => '🔴 Unavailable',
                Agent::CUSTOM_STATUS_AWAY => '🕘 Away',
                Agent::CUSTOM_STATUS_AVAILABLE => '🟢 Available',
            ]]); // use this to make sure script is only loaded once
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

        Admin::script("$('.column-fullName,.column-zendesk_agent_name,.column-zendesk_group_name,.column-zendesk_custom_field_name,th.column-custom_status,.column-limit').on('click', function() { $(this).parent('tr').iCheck('toggle'); $('tbody .icheckbox_minimal-blue.checked').length ? $('.grid-select-all-btn').show() : $('.grid-select-all-btn').hide(); $('.selected').text(() => $('.icheckbox_minimal-blue.checked').length + ' items selected')});");
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
        $this->validate($request, ['custom_status' => Rule::in(Agent::CUSTOM_STATUS_UNAVAILABLE, Agent::CUSTOM_STATUS_AVAILABLE, Agent::CUSTOM_STATUS_AWAY)]);

        $agent = Agent::where('id', $id)->firstOrFail();
        
        
        // Workaround to check same status can't be updated with the same status
        if ($agent->custom_status != $request->get('custom_status')) {
            $agent->custom_status = $request->get('custom_status');

            // to make it compatible, fil previous status column
            $status = '';
            switch ($request->get('custom_status')) {
                case Agent::CUSTOM_STATUS_AVAILABLE:
                    $status = AGENT::AVAILABLE;
                    break;
                case Agent::CUSTOM_STATUS_UNAVAILABLE:
                    $status = AGENT::UNAVAILABLE;
                    break;
                case Agent::CUSTOM_STATUS_AWAY:
                    break;
            }

            ($status && $agent->status != $status) ? $agent->status = $status : '';

            $agent->save();
            return response()->json(["status" => "Sucess updating availability status"], 200);
        }
 
        return response()->json(["status" => "bad"], 400);
    }    

    public function updateBulk(Request $request) {
        // $this->validate($request, ['custom_status' => Rule::in(Agent::CUSTOM_STATUS_UNAVAILABLE, Agent::CUSTOM_STATUS_AVAILABLE, Agent::CUSTOM_STATUS_AWAY)]);
        $ids = $request->get('ids');
        debugbar()->log($request->all());
        $agents = Agent::whereIn('id', $ids)->get();
        
        $agents->each(function($agent) use ($request) {
            // Workaround to check same status can't be updated with the same status
            if ($agent->custom_status != $request->get('custom_status')) {
                $agent->custom_status = $request->get('custom_status');
                debugbar()->log($request->all());
                // to make it compatible, fil previous status column
                $status = '';
                switch ($request->get('custom_status')) {
                    case Agent::CUSTOM_STATUS_AVAILABLE:
                        $status = AGENT::AVAILABLE;
                        break;
                    case Agent::CUSTOM_STATUS_UNAVAILABLE:
                        $status = AGENT::UNAVAILABLE;
                        break;
                    case Agent::CUSTOM_STATUS_AWAY:
                        break;
                }

                ($status && $agent->status != $status) ? $agent->status = $status : '';

                $agent->save();
            }
        });
        return response()->json(["status" => "Sucess bulk updating availability statuses"], 200); 
    }    
    
    public function destroy() {

    }

    public function syncAll() {
        SyncAllAgents::dispatchSync();

        return response()->json(['status' => 'success syncing']);
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
