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
use Zendesk\API\HttpClient as ZendeskAPI;

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
        $client = new ZendeskAPI("contreesdemo11557827937");
        $client->setAuth('basic', ['username' => "eldien.hasmanto@treessolutions.com", 'token' => "wZX70pAKu3aNyqOEYONUdjVLCIaoBMRFXjnbi7SE"]);

        // Get available agents

        // Match available tickets to available agents
        $tickets = $client->views(360000882356)->tickets(['sort_by' => 'assignee']);        
        foreach (array_slice($tickets->tickets, 0, 4) as $ticket) {
            AssignTicket::dispatch(360278992296, $ticket->id);
        }
        
        return $content;
    }

    public function rules(Content $content) {
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
