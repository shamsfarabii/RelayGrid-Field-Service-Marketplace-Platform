<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;

class TechnicianRepository implements TechnicianRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::get();
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM technicians ORDER BY created_at DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM technicians WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO technicians (user_id, first_name, last_name, email, phone, region, status)
            VALUES (:user_id, :first_name, :last_name, :email, :phone, :region, :status)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id'    => $data['user_id'] ?? null,
            ':first_name' => $data['first_name'],
            ':last_name'  => $data['last_name'],
            ':email'      => $data['email'],
            ':phone'      => $data['phone'] ?? null,
            ':region'     => $data['region'] ?? null,
            ':status'     => $data['status'] ?? 'active',
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM technicians WHERE user_id = :user_id LIMIT 1");
        $stmt->execute([':user_id' => $userId]);

        $row = $stmt->fetch();
        return $row ?: null;
    }



    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE technicians SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }
}
