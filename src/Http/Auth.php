<?php

namespace App\Http;

use App\Database\Connection;
use PDO;

class Auth
{
    private static ?array $cachedUser = null;

    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authHeader, 7);

        $db = Connection::get();
        $sql = "SELECT u.*
                FROM user_tokens t
                JOIN users u ON u.id = t.user_id
                WHERE t.token = :token
                  AND (t.expires_at IS NULL OR t.expires_at > NOW())";

        $stmt = $db->prepare($sql);
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch();

        self::$cachedUser = $user ?: null;

        return self::$cachedUser;
    }

    public static function requireRole(array $roles): array
    {
        $user = self::user();
        if (!$user) {
            Response::error('Unauthorized', 401);
            exit;
        }

        if (!in_array($user['role'], $roles, true)) {
            Response::error('Forbidden', 403);
            exit;
        }

        return $user;
    }
}
