<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;

class UserTokenRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::get();
    }

    public function createToken(int $userId, ?\DateTimeInterface $expiresAt = null): string
    {
        $token = bin2hex(random_bytes(32));

        $sql = "INSERT INTO user_tokens (user_id, token, expires_at)
            VALUES (:user_id, :token, :expires_at)
            ON DUPLICATE KEY UPDATE
                token = VALUES(token),
                expires_at = VALUES(expires_at),
                created_at = NOW()";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id'    => $userId,
            ':token'      => $token,
            ':expires_at' => $expiresAt ? $expiresAt->format('Y-m-d H:i:s') : null,
        ]);

        return $token;
    }


    public function findUserByToken(string $token): ?array
    {
        $sql = "SELECT u.*
                FROM user_tokens t
                JOIN users u ON u.id = t.user_id
                WHERE t.token = :token
                  AND (t.expires_at IS NULL OR t.expires_at > NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':token' => $token]);

        $row = $stmt->fetch();
        return $row ?: null;
    }
}
