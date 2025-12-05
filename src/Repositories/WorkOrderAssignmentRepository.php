<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;

class WorkOrderAssignmentRepository implements WorkOrderAssignmentRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::get();
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO work_order_assignments 
                (work_order_id, technician_id, status, assigned_at)
                VALUES (:work_order_id, :technician_id, :status, :assigned_at)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':work_order_id' => $data['work_order_id'],
            ':technician_id' => $data['technician_id'],
            ':status'        => $data['status'] ?? 'pending',
            ':assigned_at'   => $data['assigned_at'] ?? date('Y-m-d H:i:s'),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        // Set appropriate timestamps by status
        $fields = ['status = :status', 'updated_at = NOW()'];
        $params = [
            ':id'     => $id,
            ':status' => $status,
        ];

        if ($status === 'accepted') {
            $fields[] = 'accepted_at = NOW()';
        }

        if ($status === 'completed') {
            $fields[] = 'completed_at = NOW()';
        }

        $sql = "UPDATE work_order_assignments
                SET " . implode(', ', $fields) . "
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function findByWorkOrder(int $workOrderId): array
    {
        $sql = "SELECT *
                FROM work_order_assignments
                WHERE work_order_id = :work_order_id
                ORDER BY created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':work_order_id' => $workOrderId]);

        return $stmt->fetchAll();
    }

    public function findByTechnician(int $technicianId): array
    {
        $sql = "SELECT *
                FROM work_order_assignments
                WHERE technician_id = :technician_id
                ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':technician_id' => $technicianId]);

        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM work_order_assignments WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}
