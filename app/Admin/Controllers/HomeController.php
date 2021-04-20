<?php

namespace App\Admin\Controllers;

use App\Agent;
use App\Group;
use Carbon\Carbon;
use App\Assignment;
use Encore\Admin\Grid;
use App\AvailabilityLog;
use Encore\Admin\Layout\Row;
use Illuminate\Http\Request;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;
use Jxlwqq\DataTable\DataTable;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class HomeController extends Controller
{
    public function index(Content $content, Request $request)
    {
        $agentQuery = Agent::disableCache();
        $filteredAgentQuery = $agentQuery;
        if ($request->current == "on") {
            $agentsWithAssignmentCount = $filteredAgentQuery->get()->map(function($agent) {
                return [
                    'full_name' => $agent->full_name,
                    'assignment_count' => count(Redis::smembers('agent:'.$agent->id.':assignedTickets'))
                ];   
            })->sortByDesc('assignment_count')->take(20);
        } else {
            $filteredAgentIds = DB::table('agents')
                            ->leftJoin('assignments', 'agents.id', '=', 'assignments.agent_id')
                            ->where('type', 'ASSIGNMENT')
                            ->where('response_status', '200')
                            ->whereBetween('assignments.created_at', [(bool)strtotime($request->from) ? Carbon::parse($request->from) : Carbon::now()->subMonth(), (bool) strtotime($request->to) ? Carbon::parse($request->to) : Carbon::now()])
                            ->select(DB::raw('agents.id, count(*) as assignment_count'))
                            ->groupBy('agents.id')
                            ->orderByDesc('assignment_count')
                            ->limit(20)
                            ->get();

            $filteredAgentQuery = $filteredAgentQuery->whereIn('id', $filteredAgentIds->pluck('id')->all());
            if ($request->availability == 'available') {
                $filteredAgentQuery->where('status', Agent::AVAILABLE);
            } else if ($request->availability == 'unavailable') {
                $filteredAgentQuery->where('status', Agent::UNAVAILABLE);
            }

            $agents = $filteredAgentQuery->get();
    
            $assignmentCountByAgentId = $filteredAgentIds->mapWithKeys(function($filteredAgentId) {
                return [$filteredAgentId->id => $filteredAgentId->assignment_count];
            });
            $agentsWithAssignmentCount = $agents->each(function($agent) use ($assignmentCountByAgentId) {
                                    $assignmentCount = $assignmentCountByAgentId->get($agent->id);
                                    $agent->assignment_count = $assignmentCount;
                                })
                            ->sortByDesc('assignment_count');    
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
                $availabilityLogs = AvailabilityLog::latest("created_at")->limit(10)->get(["created_at", "status", "agent_name"]);

                // Latest Assignments table
                $headers = ['Date', 'Agent', 'Ticket ID', 'Title', "Type"];
                $rows = Assignment::latest("created_at")->limit(10)->get(["created_at", "agent_name", "zendesk_ticket_id", "zendesk_ticket_subject", "type"])->toArray();
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

        $grid->model()->orderBy('created_at', 'desc');

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

        $grid->model()->orderBy('created_at', 'desc');

        $grid->column("created_at");
        $grid->column("status");
        $grid->column("agent_name");

        return $content->body($grid);
    }        

    private function totalAssignmentChartTitle($request)
    {
        $from = $request->from ?: "a month ago";
        $to = $request->to ?: "now";
        $time = "from $from to $to";
        
        return "Agents by total assignment(s) $time";
    }
}
