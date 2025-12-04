<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;

class WorkOrderRepository implements WorkOrderRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::get();
    }

    public function findAll(): array
    {
        return $this->db->query("SELECT * FROM work_orders ORDER BY id DESC")->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM work_orders WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO work_orders 
        (company_id, title, description, status, payout_amount, currency, scheduled_start_at, scheduled_end_at)
        VALUES (:company_id, :title, :description, :status, :payout_amount, :currency, :scheduled_start_at, :scheduled_end_at)";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':company_id' => $data['company_id'],
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':status' => $data['status'] ?? 'draft',
            ':payout_amount' => $data['payout_amount'] ?? 0,
            ':currency' => $data['currency'] ?? 'USD',
            ':scheduled_start_at' => $data['scheduled_start_at'] ?? null,
            ':scheduled_end_at' => $data['scheduled_end_at'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }


    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        $params[':id'] = $id;

        $sql = "UPDATE work_orders SET " . implode(', ', $fields) . " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
