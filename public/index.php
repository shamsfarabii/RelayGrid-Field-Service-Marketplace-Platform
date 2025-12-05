<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/env.php';

use App\Http\Response;
use App\Routing\Router;
use App\Controllers\AuthController;
use App\Controllers\AssignmentController;
use App\Exceptions\ValidationException;
use App\Http\Cors;

Cors::handle();


if (env('APP_ENV', 'local') === 'local') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
}

set_error_handler(function ($severity, $message, $file, $line) {
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function (\Throwable $e) {
    if ($e instanceof ValidationException) {
        Response::error($e->getMessage(), 422, $e->getErrors());
        return;
    }

    $logMessage = sprintf(
        "[%s] %s in %s:%d\nStack trace:\n%s\n",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );

    error_log($logMessage);

    Response::json([
        'success' => false,
        'status'  => 500,
        'error'   => 'Internal server error',
    ], 500);
});



use App\Controllers\CompanyController;
use App\Controllers\TechnicianController;
use App\Controllers\WorkOrderController;

$router = new Router();

$router->post('/auth/register', AuthController::class . '@register');
$router->post('/auth/login', AuthController::class . '@login');

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

$router->post('/work-orders/{id}/assignments', AssignmentController::class . '@storeForWorkOrder');
$router->put('/assignments/{id}', AssignmentController::class . '@updateStatus');

$router->get('/work-orders/{id}/assignments', AssignmentController::class . '@indexForWorkOrder');

$router->get('/me/assignments', AssignmentController::class . '@indexForMe');


$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
