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
        // Get list of assignments [date, who, ticket_id, ticket name]
        // Get list of reassignment
        // Assigned in the last 24 hours
        // Assign today



        return $content
            ->title('Dashboard')
            ->description('Description...')
            // ->row(Dashboard::title())
            ->row(function (Row $row) {
                dump("total assignment by agent");
                foreach (Agent::all() as $agent) {
                    dump($agent->full_name . ": " . $agent->assignments()->count());
                }
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

    public function schedules(Content $content) {
        // $john = \Cache::tags(["people", "author"])->put("John", "detail_john");
        return $content;
    }

    public function tasks(Content $content) {
        $client = new ZendeskAPI("contreesdemo11557827937");
        $client->setAuth('basic', ['username' => "eldien.hasmanto@treessolutions.com", 'token' => "wZX70pAKu3aNyqOEYONUdjVLCIaoBMRFXjnbi7SE"]);

        // Get available agents

        // Match available tickets to available agents
        return $content;
    }

    public function groups(Content $content) {
        return $content;
    }    

    public function rules(Content $content) {
        return $content;
    }    

    public function logs(Content $content) {
        return $content;
    }        

    public function jobs(Content $content) {
        return $content;
    }
}
