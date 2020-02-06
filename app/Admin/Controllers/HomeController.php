<?php

namespace App\Admin\Controllers;

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
use App\Admin\Actions\Post\BatchReplicate;
use App\Admin\Actions\Post\ImportPost;
use App\Jobs\AssignTicket;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        return $content
            ->title('Dashboard')
            ->description('Description...')
            ->row(Dashboard::title())
            ->row(function (Row $row) {

                // $row->column(4, function (Column $column) {
                //     $column->append(Dashboard::environment());
                // });

                // $row->column(4, function (Column $column) {
                //     $column->append(Dashboard::extensions());
                // });

                // $row->column(4, function (Column $column) {
                //     $column->append(Dashboard::dependencies());
                // });
            });
    }

    public function scheduler(Content $content) {
        return $content;
    }

    public function queues(Content $content) {
        AssignTicket::dispatch()->onQueue('zd');
        return $content;
    }

    public function groups(Content $content) {
        return $content;
    }

    public function logs(Content $content) {
        return $content;
    }        

    public function jobs(Content $content) {
        return $content;
    }
}
