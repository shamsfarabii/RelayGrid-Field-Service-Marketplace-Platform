<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Routing\Router;

$router = new Router();

$router->get('/work-orders', 'App\Controllers\WorkOrderController@index');
$router->get('/work-orders/{id}', 'App\Controllers\WorkOrderController@show');

$router->post('/work-orders', 'App\Controllers\WorkOrderController@store');
$router->put('/work-orders/{id}', 'App\Controllers\WorkOrderController@update');

$router->delete('/work-orders/{id}', 'App\Controllers\WorkOrderController@destroy');

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
