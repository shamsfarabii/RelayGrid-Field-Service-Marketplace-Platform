<?php

namespace App\Http;

class Cors
{
    public static function handle(): void
    {
        // Adjust to your frontend URL
        $allowedOrigin = $_ENV['FRONTEND_URL'] ?? '*';

        header("Access-Control-Allow-Origin: {$allowedOrigin}");
        header("Access-Control-Allow-Headers: Authorization, Content-Type, Accept");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Credentials: true");

        // Pre-flight (OPTIONS request)
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}
