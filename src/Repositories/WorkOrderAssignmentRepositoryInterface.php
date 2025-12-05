<?php

namespace App\Repositories;

interface WorkOrderAssignmentRepositoryInterface
{
    public function create(array $data): int;

    public function updateStatus(int $id, string $status): bool;

    public function findByWorkOrder(int $workOrderId): array;

    public function findByTechnician(int $technicianId): array;

    public function findById(int $id): ?array;
}
