<?php

namespace App\Admin\Controllers;

use App\Agent;
use App\Group;
use App\Assignment;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use App\Jobs\AssignTicket;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Widgets\Table;
use Jxlwqq\DataTable\DataTable;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use App\Admin\Actions\Post\ImportPost;
use Encore\Admin\Controllers\Dashboard;
use Zendesk\API\HttpClient as ZendeskAPI;
use App\Admin\Actions\Post\BatchReplicate;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        $full_names = [];
        $assignment_counts = []; 
        $total_available_agents = 0;
        foreach (Agent::get() as $agent) {
            $full_names[] = $agent->full_name;
            $assignment_counts[] = $agent->assignments()->count();

            if ($agent->status) {
                $total_available_agents++;
            }
        }

        $agents = Agent::disableCache()->withCount(['assignments'])->get();
        $total_available_agents = $agents->sum('status');

        return $content
            ->title('Dashboard')
            ->description('Description...')
            ->row(function (Row $row) use ($total_available_agents, $agents) {
                $agentsByAssignment = $agents->slice(0, 20)->sortByDesc('assignments_count');
                $full_names = $agentsByAssignment->pluck('full_name');
                $assignment_counts = $agentsByAssignment->pluck('assignments_count');
                
                // table
                $headers = ['Date', 'Agent', 'Ticket ID', 'Title', "Type"];
                $rows = Assignment::latest("created_at")->limit(10)->get(["created_at", "agent_name", "ticket_id", "ticket_name", "type"])->toArray();
                $style = ['table-bordered','table-hover', 'table-striped'];

                $options = [
                    'lengthChange' => false,
                    // 'ordering' => true,
                    'info' => true,
                    'autoWidth' => false,
                ];

                $dataTable = new DataTable($headers, $rows, $style, $options);        
                
                // $row->column(8, new Box("Agents by total assignments", view('roundrobin.charts.chartjs', compact('full_names', 'assignment_counts'))));
                $row->column(8, new Box("Agent(s) by number of assignments", view('roundrobin.dashboard.agentTotalAssignments', compact('full_names', 'assignment_counts'))));
                $row->column(4, new Box("Agent(s) available", $total_available_agents ?: "None"));
                // $row->column(4, new Box("Availability logs", $total_available_agents ?: "None"));
                
                // $row->column(4, new Box("Active Task(s)", $total_available_agents ?: "None"));
                $row->column(12, new Box("Latest assignments", view('roundrobin.logs', compact('dataTable'))));
                // $row->column(4, function (Column $column) use ($full_names, $assignment_counts) {
                //     // $column->append((new Box('Agents by ticket assigned within 24 hours', view('roundrobin.charts.chartjs', compact('full_names', 'assignment_counts')))));
                //     $column->body('roundrobin.charts.chartjs', compact('full_names', 'assignment_counts'));
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
        $client = new ZendeskAPI(env("ZENDESK_SUBDOMAIN","contreesdemo11557827937"));
        $client->setAuth('basic', ['username' => "eldien.hasmanto@treessolutions.com", 'token' => "wZX70pAKu3aNyqOEYONUdjVLCIaoBMRFXjnbi7SE"]);

        // Get available agents

        // Match available tickets to available agents
        return $content;
    }

    public function groups(Content $content) {
        return $content
            ->title('Groups')
            ->row(function (Row $row) {

                            foreach (Group::all() as $group) {
                                $row->column(6, new Grid(new Agent, function(Grid $grid) use ($group) {
                                    // $grid->paginate(5);
                                    $grid->model()->where('zendesk_group_id', $group->group_id);

                                    $grid->setTitle($group->group_name);

                                    $grid->disableColumnSelector();
                                    $grid->disableExport();
                                    // $grid->disableCreateButton();
                                    $grid->disableFilter();
                                    $grid->batchActions(function ($batch) {
                                        $batch->disableDelete();
                                    });
                                    $grid->disableCreateButton();
                                    $grid->disableActions();
                                    $grid->disableBatchActions();
                                    
                                    $grid->fullName();
                                }));
                            }
            });
    }    

    public function rules(Content $content) {
        return $content;
    }    

    public function logs(Content $content) {
        $grid = Admin::grid(new Assignment, function (Grid $grid) {
            $grid->disableColumnSelector();
            $grid->disableRowSelector();
            $grid->disableCreateButton();
            $grid->disableFilter();
            $grid->disableActions();

            $grid->column("created_at");
            $grid->column("agent_name");
            $grid->column("ticket_id");
            $grid->column("ticket_name");
            $grid->column("type");
        });

        return $content->body($grid);
    }        

    public function jobs(Content $content) {
    }
}
