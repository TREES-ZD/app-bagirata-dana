<?php

use Illuminate\Routing\Router;
use Illuminate\Http\Request;
use App\Agent;
use Zendesk\API\HttpClient as ZendeskAPI;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('admin.home');

    $router->get('/agents', 'AgentController@index');    
    $router->get('/agents/create', 'AgentController@create');
    $router->post('/agents', 'AgentController@store');
    $router->get('/agents/{id}', 'AgentController@show');
    $router->get('/agents/{id}/edit', 'AgentController@edit');
    $router->put('/agents/{id}', 'AgentController@update');
    $router->delete('/agents/{id}', 'AgentController@destroy');

    $router->get('/schedules', 'HomeController@schedules');
    $router->get('/jobs', 'HomeController@jobs');
    $router->get('/queues', 'HomeController@queues');
    $router->get('/rules', 'HomeController@rules');
    $router->get('/logs', 'HomeController@logs');
});

Route::post('run', function() {
    $subdomain = "contreesdemo11557827937";
    $username  = "eldien.hasmanto@treessolutions.com";
    $token     = "2HJtvL35BSsWsVR4b3ZCxvYhLGYcAacP2EyFKGki"; // replace this with your token
    
    $client = new ZendeskAPI($subdomain);
    $client->setAuth('basic', ['username' => $username, 'token' => $token]);

    $tickets = $client->views(360001440115)->tickets();

    // Assign round robin
    $agents = Agent::where('status', true)->get();

    $totalAgents = $agents->count();
    $totalTickets = count($tickets->tickets);

    foreach ($tickets->tickets as $i => $ticket) {
        $agentNum = ($i % $totalAgents);
        $client->tickets()->update($ticket->id, [
            "group_id" => "360000974835"
        ]);
    }

    return response()->json($tickets->tickets);
});