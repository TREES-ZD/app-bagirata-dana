<?php

namespace App\Admin\Controllers;

use App\Agent;
use App\Group;
use App\Assignment;
use Encore\Admin\Grid;
use App\AvailabilityLog;
use Encore\Admin\Layout\Row;
use Illuminate\Http\Request;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;
use Jxlwqq\DataTable\DataTable;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index(Content $content, Request $request)
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
            ->title('Home')
            ->description('Description...')
            ->row(function (Row $row) use ($total_available_agents, $agents) {
                $agentsByAssignment = $agents->sortByDesc('assignments_count')->slice(0, 20);
                $full_names = $agentsByAssignment->pluck('full_name');
                $assignment_counts = $agentsByAssignment->pluck('assignments_count');
                
                // Availability Logs table
                $availabilityLogs = AvailabilityLog::latest("created_at")->limit(10)->get(["created_at", "status", "agent_name"]);

                // Latest Assignments table
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
                
                $row->column(8, new Box("Agent(s) by number of assignments", view('roundrobin.dashboard.agentTotalAssignments', compact('full_names', 'assignment_counts'))));
                $row->column(4, new Box("Agent(s) available", $total_available_agents ?: "None"));
                $row->column(4, new Box("Availability logs", view('roundrobin.dashboard.availabilityLogs', compact('availabilityLogs'))));
                
                $row->column(12, new Box("Latest assignments", view('roundrobin.logs', compact('dataTable'))));
            });
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

    public function assignment_logs(Content $content) {
        $grid = Admin::grid(new Assignment, function (Grid $grid) {
            $grid->disableColumnSelector();
            $grid->disableRowSelector();
            $grid->disableCreateButton();
            $grid->disableFilter();
            $grid->disableActions();

            $grid->model()->orderBy('created_at', 'desc');

            $grid->column("created_at");
            $grid->column("agent_name");
            $grid->column("ticket_id");
            $grid->column("ticket_name");
            $grid->column("type");
        });

        return $content->body($grid);
    }
    
    public function availability_logs(Content $content) {
        $grid = Admin::grid(new AvailabilityLog, function (Grid $grid) {
            $grid->disableColumnSelector();
            $grid->disableRowSelector();
            $grid->disableCreateButton();
            $grid->disableFilter();
            $grid->disableActions();

            $grid->model()->orderBy('created_at', 'desc');

            $grid->column("created_at");
            $grid->column("status");
            $grid->column("agent_name");
        });

        return $content->body($grid);
    }        
}
