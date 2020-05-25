<?php

namespace App\Admin\Controllers;

use App\Task;
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
use Illuminate\Support\Facades\DB;

class RuleController extends Controller
{
    public function index(Content $content) {
        $grid = Admin::grid(new Agent, function (Grid $grid) {
            $grid->disableExport();
            $grid->disableCreateButton();
            $grid->disableActions();
            $grid->disableFilter();
            $grid->batchActions(function ($batch) {
                $batch->disableDelete();
                // $batch->add(new BatchDelete());
            });
            $grid->disableBatchActions();
            $grid->disableColumnSelector();

            $tasks = Task::all();

            // column not in table
            $grid->fullName("Agent");

            $tasks->each(function($task) use ($grid) {
                $grid->column($task->id, $task->zendesk_view_title)->display(function () use ($task) {;
                    $rule = $this->rules->contains($task->id);
                    return $rule && $this->rules->first() ? $this->rules->first()->pivot->priority : "-";
                })->editable();
            });
         
        });

        return $content->body($grid);
    }
    
    public function update(Request $request, $id) {
        \Debugbar::info($request->all());
        \Debugbar::info($id);
        $agent = Agent::findOrFail($id);
        $agent->rules()->attach($request->name, array('priority' => $request->value));

        return $agent;
    }    
}
