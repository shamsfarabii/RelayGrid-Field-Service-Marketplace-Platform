<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Routing\Router;

use App\Controllers\CompanyController;
use App\Controllers\TechnicianController;
use App\Controllers\WorkOrderController;

$router = new Router();

$router->get('/work-orders', WorkOrderController::class . '@index');
$router->get('/work-orders/{id}', WorkOrderController::class . '@show');
$router->post('/work-orders', WorkOrderController::class . '@store');
$router->post('/work-orders/bulk', WorkOrderController::class . '@storeBulk');
$router->put('/work-orders/{id}', WorkOrderController::class . '@update');
$router->delete('/work-orders/{id}', WorkOrderController::class . '@destroy');

$router->get('/companies', CompanyController::class . '@index');
$router->get('/companies/{id}', CompanyController::class . '@show');
$router->post('/company', CompanyController::class . '@store');
$router->put('/company/{id}', CompanyController::class . '@update');
$router->delete('/company/{id}', CompanyController::class . '@destroy');

$router->get('/technicians', TechnicianController::class . '@index');
$router->get('/technician/{id}', TechnicianController::class . '@show');
$router->post('/technician', TechnicianController::class . '@store');
$router->put('/technician/{id}', TechnicianController::class . '@update');

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
