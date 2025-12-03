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
        // implementation tomorrow
        return 0;
    }

    public function update(int $id, array $data): bool
    {
        // implementation tomorrow
        return false;
    }
}
