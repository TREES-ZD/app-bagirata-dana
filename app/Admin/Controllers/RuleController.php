<?php

namespace App\Admin\Controllers;

use App\Models\Task;
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
use Illuminate\Support\Facades\DB;

class RuleController extends Controller
{
    public function index(Content $content) {
        $grid = Admin::grid(new Agent, function (Grid $grid) {
            $grid->disableExport();
            $grid->disableCreateButton();
            $grid->disableActions();
            // $grid->disableFilter();
            $grid->batchActions(function ($batch) {
                $batch->disableDelete();
                // $batch->add(new BatchDelete());
            });
            $grid->disableBatchActions();
            $grid->disableColumnSelector();

            $grid->filter(function($filter){
                // Remove the default id filter
                $filter->disableIdFilter();

                // Add a column filter
                $filter->column(1/2, function($filter) {
                    $filter->ilike('zendesk_agent_name', 'Assignee');
                    $filter->ilike('zendesk_group_name', 'Group');
                    $filter->ilike('zendesk_custom_field_name', 'Agent Name');
                    // $filter->in('status', 'Availability')->radio([
                    //     '' => 'All',
                    //     true => 'Available',
                    //     false => 'Unavailable',
                    // ]);
                    $filter->equal('custom_status')->select([
                        Agent::CUSTOM_STATUS_UNAVAILABLE => '🔴 Unavailable',
                        Agent::CUSTOM_STATUS_AVAILABLE => '🟢 Available',
                        Agent::CUSTOM_STATUS_AWAY => '🕘 Away' 
                    ]);
                });
                $filter->column(1/2, function($filter) {
                    $filter->layoutOnly()->ilike('task_view_title', 'Task title'); //tidak panggil database
                    $filter->layoutOnly()->in('task_status', 'Task status')->radio([
                        '' => 'All',
                        true => 'Enabled',
                        false => 'Disabled',
                    ]);
                    $filter->layoutOnly()->in('only_show_assigned', 'Only show assigned')->select([
                        true => 'Yes',
                        false => 'No'
                    ]);
                });

            });

            $taskBuilder = Task::query();
            if ($title = request('task_view_title')) {
                $taskBuilder->where('zendesk_view_title', 'like', "%".$title."%");
            }
            if ($status = request('task_status')) {
                $taskBuilder->where('enabled', (bool) $status);
            }

            $tasks = $taskBuilder->get();
            $rules = DB::table('rules')->whereIn('task_id', $tasks->pluck('id'))->get();
            $rulesByTask = $rules->groupBy('task_id');
            $tasks->each(function($task) use ($rulesByTask) {
                $task->rulesTable = $rulesByTask->get($task->id) ?: collect();
            });
            
            if (request('only_show_assigned')) {
                $agentIds = $rules->pluck('agent_id')->unique();
                $grid->model()->whereIn('id', $agentIds->all());
            }
            
            // column not in table
            $grid->fixColumns(1, 0);
            $grid->paginate(30);

            // $grid->fullName("Agent");
            $grid->column('Agent')->display(function ($title) {
                debugbar()->debug($this->status); //get model
                $html = $this->fullName; 
                if ($this->custom_status == Agent::CUSTOM_STATUS_AVAILABLE) {
                    return $html . '  🟢';
                } else if ($this->custom_status == Agent::CUSTOM_STATUS_AWAY) {
                    return $html . '  🕘';
                }
                return $html . ' 🔴';
            });            

            $tasks->each(function($task) use ($grid) {
                $grid->column($task->id, sprintf("%s", $task->zendesk_view_title))->display(function () use ($task) {
                    $rule = $task->rulesTable->firstWhere('agent_id', $this->id);
                    return $rule ? $rule->priority : "-";
                })->editable();
            });
         
        });

        return $content->body($grid);
    }
    
    public function update(Request $request, $id) {
        $task_id = $request->name;
        $agent = Agent::findOrFail($id);

        if ($request->value == 0 || $request->value == "-") {
            $agent->rules()->detach($task_id);
            return $agent;
        }

        $agent->rules()->syncWithoutDetaching($task_id, array('priority' => $request->value));
        return $agent;
    }    
}
