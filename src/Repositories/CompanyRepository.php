<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;

class CompanyRepository implements CompanyRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::get();
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM companies ORDER BY id ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM companies WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO companies (user_id, name, contact_email, contact_phone)
            VALUES (:user_id, :name, :contact_email, :contact_phone)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id'       => $data['user_id'] ?? null,
            ':name'          => $data['name'],
            ':contact_email' => $data['contact_email'],
            ':contact_phone' => $data['contact_phone'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findByUserId(int $userId): ?array
{
    $stmt = $this->db->prepare("SELECT * FROM companies WHERE user_id = :user_id LIMIT 1");
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

        $sql = "UPDATE companies SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM companies WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
