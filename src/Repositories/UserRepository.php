<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::get();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $email, string $passwordHash, string $role = 'company'): int
    {
        $sql = "INSERT INTO users (email, password_hash, role)
                VALUES (:email, :password_hash, :role)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':email'         => $email,
            ':password_hash' => $passwordHash,
            ':role'          => $role,
        ]);

        return (int)$this->db->lastInsertId();
    }
}
