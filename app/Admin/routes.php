<?php


use App\Models\Task;
use App\Models\Agent;
// use Encore\Admin\Admin;
use App\Models\Assignment;
use App\Repositories\JobStatusRepository;
use DebugBar\DebugBar;
use Huddle\Zendesk\Facades\Zendesk;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use App\Services\ZendeskService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use HerokuClient\Client as HerokuClient;
use Zendesk\API\HttpClient as ZendeskAPI;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {
    $router->resource('auth/users', 'UserController')->names('admin.auth.users');

    $router->get('/', 'HomeController@index')->name('admin.home');

    $router->get('/reset', function() {
        $status = Redis::command('FLUSHALL');
        if ($status)
        {
            return response()->json(['status' => 'reset']);
        }
        
        return redirect()->back();
    });

    $router->get('/agents', 'AgentController@index');    
    $router->get('/agents/create', 'AgentController@create');
    $router->post('/agents', 'AgentController@store');
    $router->get('/agents/sync', 'AgentController@sync');
    $router->get('/agents/{id}', 'AgentController@show');
    $router->get('/agents/{id}/edit', 'AgentController@edit');
    $router->put('/agents/bulk', 'AgentController@updateBulk')->name('agent.updateBulk');
    $router->put('/agents/{id}', 'AgentController@update')->name('agent.update');
    $router->delete('/agents/{id}', 'AgentController@destroy');
    $router->post("/agents/sync", 'AgentController@sync');
    $router->post("/agents/syncAll", 'AgentController@syncAll')->middleware('throttle:1,1');

    $router->get('/tasks', 'TaskController@index');
    $router->put('/tasks/{id}', 'TaskController@update');
    $router->get('/tasks/sync', 'TaskController@sync');

    $router->get('/rules', 'RuleController@index');
    $router->put('/rules/{id}', 'RuleController@update');

    $router->get('/schedules', 'HomeController@schedules');
    $router->get('/jobs', 'HomeController@jobs');
    $router->get('/groups', 'HomeController@groups');
    $router->get('/assignment_logs', 'HomeController@assignment_logs');
    $router->get('/availability_logs', 'HomeController@availability_logs');

});
Route::post('api/v2/reopen', function(Request $request) {
    $ticket_id = request()->get('ticket_id');
    Redis::sadd('ids', $ticket_id);
    return response()->json(["ticket_id" => $ticket_id]);
});

Route::get('api/v2/reopen/run', function(Request $request) {
    dd(Redis::smembers('ids'));
});

Route::get('run', function(Request $request) {
    if ($request->has('_pjax')) {
        App\Jobs\Assignments\AssignBatch::dispatch(Task::where('zendesk_view_id', $request->view_id)->get())->onQueue('assignment');    
    }
    return redirect()->back();
});
Route::post('run', function() {
    App\Jobs\Assignments\AssignBatch::dispatchNow(Task::where('id', 1)->get());
    return response()->json();
});
Route::post('unassign', function(Request $request) {
    App\Jobs\Agent\UnassignTickets::dispatchNow(Agent::find($request->agent_id))->onQueue('unassignment');
    return response()->json();
});
Route::post('syncAgents', function(Request $request) {
    App\Jobs\SyncAgents::dispatch();
    return response()->json();
});
Route::get('refresh', function(Request $request) {
    app(ZendeskService::class)->refresh();
    return redirect()->back();
});
Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
Route::get('sleep', function(Request $request) {
    sleep(20);
    return response()->json();
});
Route::get('long_query', function(Request $request) {
    DB::connection()->select(DB::raw("SELECT pg_sleep(60)"));
    return response()->json();
});
Route::get('heavy', function(Request $request) {
    $a = 0;
    for($i = 0; $i < 1000000000; $i++) {
        $a += $i;
   }
    return response()->json();
});
Route::post("__restart", function() {
    $herokutoken = env('HEROKU_TOKEN', "bc39b02d-a1af-4f0e-87df-f0ab67341757");
    $heroku = new HerokuClient([
        'apiKey' => $herokutoken,
    ]);

    $herokuappname = env('HEROKU_APP_NAME', "app-bagirata-dana");
    $heroku->delete("apps/$herokuappname/dynos/worker");
    $status = $heroku->getLastHttpResponse()->getStatusCode();
    return ["status" => $status];
});
