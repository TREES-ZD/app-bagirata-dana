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
use App\Task;

class TaskController extends Controller
{
    public function index(Content $content) {
        $grid = Admin::grid(new Task, function (Grid $grid) {
            $grid->disableColumnSelector();
            // $grid->disableActions();  
            $grid->disableFilter();  
            $grid->enabled()->select([
                true => 'Yes',
                false => 'No',
            ]);      
            $grid->column('Run')->display(function ($title) {
                return '<a href="/run"><i class="fa fa-play"></i></a>';
            });            
            $grid->zendesk_view_title("View title");
            $grid->zendesk_view_id("View id");
            $grid->interval();
            $grid->assign_to_agent("Assign to Agent");
            $grid->limit();
        });

        return $content->body($grid);
    }

}
