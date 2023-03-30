<?php

namespace App\Admin\Controllers;

use App\Models\Agent;
use App\Models\Group;
use Carbon\Carbon;
use App\Models\Assignment;
use Encore\Admin\Grid;
use App\Models\AvailabilityLog;
use Encore\Admin\Layout\Row;
use Illuminate\Http\Request;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;
use Jxlwqq\DataTable\DataTable;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Repositories\AgentRepository;
use Illuminate\Support\Facades\Redis;

class HomeController extends Controller
{
    public function index(Content $content, Request $request, AgentRepository $agentRepo)
    {
        if ($request->current == "on") {
            $agentsWithAssignmentCount = Agent::disableCache()->get()->map(function($agent) {
                return [
                    'full_name' => $agent->full_name,
                    'assignment_count' => count(Redis::smembers('agent:'.$agent->id.':assignedTickets'))
                ];   
            })->sortByDesc('assignment_count')->take(20);
        } else {
            $agentsWithAssignmentCount = $agentRepo->getWithAssignmentsCount($request->from, $request->to, $request->availability);
        }
        
        $totalAvailableAgents = Agent::disableCache()->where('status', Agent::AVAILABLE)->count();
        $totalAssignmentChartTitle = $this->totalAssignmentChartTitle($request);
        return $content
            ->title('Home')
            ->description('Description...')
            ->row(function (Row $row) use ($agentsWithAssignmentCount, $totalAvailableAgents, $totalAssignmentChartTitle) {
                $full_names = $agentsWithAssignmentCount->pluck('full_name');
                $assignment_counts = $agentsWithAssignmentCount->pluck('assignment_count');
                
                // Availability Logs table
                $availabilityLogs = AvailabilityLog::latest("id")->limit(10)->get(["created_at", "status", "agent_name"]);

                // Latest Assignments table
                $headers = ['Date', 'Agent', 'Ticket ID', 'Title', "Type"];
                $rows = Assignment::latest("id")->limit(10)->get(["created_at", "agent_name", "zendesk_ticket_id", "zendesk_ticket_subject", "type"])->toArray();
                $style = ['table-bordered','table-hover', 'table-striped'];
                $options = [
                    'lengthChange' => false,
                    // 'ordering' => true,
                    'info' => true,
                    'autoWidth' => false,
                ];
                $dataTable = new DataTable($headers, $rows, $style, $options);        
                
                $row->column(8, new Box("Agent(s) by number of assignments", view('roundrobin.dashboard.agentTotalAssignments', compact('full_names', 'assignment_counts', 'totalAssignmentChartTitle'))));
                $row->column(4, new Box("Agent(s) available", $totalAvailableAgents ?: "None"));
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
        $grid = new Grid(new Assignment());
        $grid->disableColumnSelector();
        $grid->disableRowSelector();
        $grid->disableCreateButton();
        $grid->disableFilter();
        $grid->disableActions();

        $grid->model()->orderBy('id', 'desc');

        $grid->column("created_at");
        $grid->column("agent_name");
        $grid->column('zendesk_ticket_id', 'Ticket ID')->display(function () {
            $subdomain = config('zendesk-laravel.subdomain');
            $html = sprintf("<a href=\"https://%s.zendesk.com/agent/tickets/%s\">#%s</a>", $subdomain, $this->zendesk_ticket_id, $this->zendesk_ticket_id);
            return $html;
            
        });
        $grid->column("zendesk_ticket_subject");
        $grid->column("type");    

        return $content->body($grid);
    }
    
    public function availability_logs(Content $content) {
        $grid = new Grid(new AvailabilityLog());
        $grid->disableColumnSelector();
        $grid->disableRowSelector();
        $grid->disableCreateButton();
        $grid->disableFilter();
        $grid->disableActions();

        $grid->model()->orderBy('id', 'desc');

        $grid->column("created_at");
        $grid->column("status");
        $grid->column("agent_name");

        return $content->body($grid);
    }        

    private function totalAssignmentChartTitle($request)
    {
        $from = $request->from ?: "today";
        $to = $request->to ?: "now";
        $time = "from $from to $to";

        $subject = $request->availability == "available" || $request->availability == "unavailable" ? "Current $request->availability agent(s)" : "All agents";
        
        return "$subject by total assignment(s) $time";
    }
}
