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
        return $this->db->query("SELECT * FROM work_orders ORDER BY id ASC")->fetchAll();
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

        $stmt = $this->db->prepare(query: $sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM work_orders WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function findPaginated(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT * FROM work_orders
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findByCompanyStatusAndDateRange(
        int $companyId,
        ?string $status,
        ?string $startDate,
        ?string $endDate,
        int $page = 1,
        int $perPage = 20
    ): array {
        $offset = ($page - 1) * $perPage;
        $conditions = ['company_id = :company_id'];
        $params = [':company_id' => $companyId];

        if ($status !== null) {
            $conditions[] = 'status = :status';
            $params[':status'] = $status;
        }

        if ($startDate !== null) {
            $conditions[] = 'scheduled_start_at >= :start';
            $params[':start'] = $startDate;
        }

        if ($endDate !== null) {
            $conditions[] = 'scheduled_end_at <= :end';
            $params[':end'] = $endDate;
        }

        $where = 'WHERE ' . implode(' AND ', $conditions);

        $sql = "SELECT * FROM work_orders
            $where
            ORDER BY scheduled_start_at ASC
            LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function createMany(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        $sql = "INSERT INTO work_orders 
        (company_id, title, description, status, payout_amount, currency, scheduled_start_at, scheduled_end_at)
        VALUES (:company_id, :title, :description, :status, :payout_amount, :currency, :scheduled_start_at, :scheduled_end_at)";

        $stmt = $this->db->prepare($sql);

        $insertedIds = [];

        try {
            $this->db->beginTransaction();

            foreach ($items as $data) {
                $stmt->execute([
                    ':company_id'        => $data['company_id'],
                    ':title'             => $data['title'],
                    ':description'       => $data['description'] ?? null,
                    ':status'            => $data['status'] ?? 'draft',
                    ':payout_amount'     => $data['payout_amount'] ?? 0,
                    ':currency'          => $data['currency'] ?? 'USD',
                    ':scheduled_start_at' => $data['scheduled_start_at'] ?? null,
                    ':scheduled_end_at'  => $data['scheduled_end_at'] ?? null,
                ]);

                $insertedIds[] = (int)$this->db->lastInsertId();
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $insertedIds;
    }

    public function findByFilters(array $filters, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, (int)$page);
        $perPage = max(1, min(100, (int)$perPage));
        $offset = ($page - 1) * $perPage;

        $params = [];
        $where = $this->buildFilterWhereClause($filters, $params);

        $sql = "SELECT *
            FROM work_orders
            $where
            ORDER BY scheduled_start_at ASC, id ASC
            LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll();
    }


    private function buildFilterWhereClause(array $filters, array &$params): string
    {
        $conditions = [];
        $params = [];

        if (isset($filters['company_id']) && $filters['company_id'] !== '') {
            $conditions[] = 'company_id = :company_id';
            $params[':company_id'] = (int)$filters['company_id'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['start_date'])) {
            $conditions[] = 'scheduled_start_at >= :start_date';
            $params[':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $conditions[] = 'scheduled_end_at <= :end_date';
            $params[':end_date'] = $filters['end_date'];
        }

        if (empty($conditions)) {
            return '';
        }

        return 'WHERE ' . implode(' AND ', $conditions);
    }

    public function countByFilters(array $filters): int
    {
        $params = [];
        $where = $this->buildFilterWhereClause($filters, $params);

        $sql = "SELECT COUNT(*) AS cnt
            FROM work_orders
            $where";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        $row = $stmt->fetch();

        return $row ? (int)$row['cnt'] : 0;
    }
}
