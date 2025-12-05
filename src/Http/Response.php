<?php

namespace App\Http;

class Response
{
    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public static function success(array $data = [], int $status = 200): void
    {
        self::json([
            'success' => true,
            'status'  => $status,
            'data'    => $data,
        ], $status);
    }

    public static function error(string $message, int $status = 400, array $details = []): void
    {
        self::json([
            'success' => false,
            'status'  => $status,
            'error'   => $message,
            'details' => $details,
        ], $status);
    }
}
