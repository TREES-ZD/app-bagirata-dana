<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Task\BatchDeleteTask;
use App\Admin\Actions\Task\RunTask;
use App\Task;
use App\Group;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Row;
use Illuminate\Http\Request;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Widgets\Table;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use App\Admin\Actions\Task\SyncTasksAction;
use App\Jobs\SyncTasks;
use Encore\Admin\Controllers\Dashboard;

class TaskController extends Controller
{
    public function index(Content $content) {
        $groups = Group::all();
        $grid = Admin::grid(new Task, function (Grid $grid) use ($groups) {
            $grid->disableColumnSelector();
            // $grid->disableActions();  
            $grid->disableFilter();  
            $grid->disableExport();
            $grid->disableActions();
            $grid->tools(function ($tools) {
                if (Admin::user()->isAdministrator()) {
                    $tools->append(new SyncTasksAction());
                }
            });
            $grid->batchActions(function ($batch) {
                $batch->disableDelete();

                if (Admin::user()->isAdministrator()) {
                    $batch->add(new BatchDeleteTask());
                }
            });
            $grid->disableCreateButton();
            
            if (Admin::user()->isAdministrator()) {
                $grid->enabled()->select([
                    true => 'Yes',
                    false => 'No',
                ]);      

                $grid->column('Run')->display(function () {
                    $url = "/run?view_id=". urlencode($this->zendesk_view_id);
                    return '<a href="'.$url.'"><i class="fa fa-play"></i></a>';
                });
            }
            $grid->zendesk_view_title("View title");
            $grid->zendesk_view_id("View ID");
            $grid->interval();
            $grid->limit();
        });

        return $content->body($grid);
    }

    public function update(Request $request, $id) {
        \Debugbar::debug($id);
        \Debugbar::debug(request('group_id'));
        Task::find($id)->update($request->all());

        return redirect()->to('/admin/tasks');
    }
    
    public function sync(Request $request) {
        if ($request->has('_pjax')) {
            SyncTasks::dispatchNow();
        }
    
        return redirect()->back();        
    }

}
