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
use App\User;
use App\Admin\Actions\Post\BatchReplicate;
use App\Admin\Actions\Post\ImportPost;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        return $content
            ->title('Dashboard')
            ->description('Description...')
            ->row(Dashboard::title())
            ->row(function (Row $row) {

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::environment());
                });

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::extensions());
                });

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::dependencies());
                });
            });
    }

    public function scheduler(Content $content) {
        $box = new Box('Scheduler', view('welcome'));
        $box->style('info');

        $content = $content
            ->title('Scheduler')
            ->row($box);

            $content->row(function(Row $row) {
                $row->column(4, 'foo');
                $row->column(4, 'bar');
                $row->column(4, 'baz');
            });          
    
        return $content;
    }

    public function agents(Content $content) {
        $box = new Box('Agents', view('roundrobin.agents'));
        $box->style('info');

        $content = $content
            ->body($this->grid());
    
        return $content;
    }
    
    protected function grid()
    {
        return Admin::grid(new User, function (Grid $grid) {
            $grid->tools(function (Grid\Tools $tools) {
                $tools->append(new BatchReplicate());
                $tools->append(new ImportPost());
            });
            
            $grid->id('ID')->sortable();
            $grid->column('approved')->bool();
            
            $grid->column("Zendesk ID");
            $grid->column("Name");
            $grid->column("Unique Identifier");
        });
    }    
}
