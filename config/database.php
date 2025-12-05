<?php

require_once __DIR__ . '/env.php';

return [
    'host'     => env('DB_HOST', '127.0.0.1'),
    'database' => env('DB_NAME', 'fielddesk_legacy'),
    'username' => env('DB_USER', 'root'),
    'password' => env('DB_PASS', ''),
    'charset'  => env('DB_CHARSET', 'utf8mb4'),
];
