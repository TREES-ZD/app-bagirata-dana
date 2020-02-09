<?php

use Illuminate\Routing\Router;
use Illuminate\Http\Request;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('admin.home');

    $router->get('/agents', 'AgentController@index');
    $router->get('/agents/create', 'AgentController@create');
    $router->post('/agents/store', 'AgentController@store');
    $router->get('/agents/{id}', 'AgentController@show');
    $router->get('/agents/{id}/edit', 'AgentController@edit');
    $router->put('/agents/{id}/update', 'AgentController@update');
    $router->delete('/agents/{id}', 'AgentController@destroy');

    $router->get('/scheduler', 'HomeController@scheduler');
    $router->get('/jobs', 'HomeController@jobs');
    $router->get('/queues', 'HomeController@queues');
    $router->get('/rules', 'HomeController@rules');
    $router->get('/logs', 'HomeController@logs');
});
