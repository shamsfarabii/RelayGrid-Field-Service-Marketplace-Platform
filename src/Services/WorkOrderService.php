<?php

namespace App\Services;

use App\Repositories\WorkOrderRepositoryInterface;
use App\Exceptions\ValidationException;


class WorkOrderService
{
    private WorkOrderRepositoryInterface $repo;

    private array $allowedStatusTransitions = [
        'draft' => ['dispatched', 'completed', 'cancelled'],
        'dispatched' => ['in_progress', 'completed', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];


    private function assertStatusTransition(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }

        $allowed = $this->allowedStatusTransitions[$from] ?? [];

        if (!in_array($to, $allowed, true)) {
            throw new \Exception("Invalid status transition from {$from} to {$to}");
        }
    }


    public function __construct(WorkOrderRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function validateCreate(array $data): array
    {
        $required = ['company_id', 'title', 'status'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("$field is required");
            }
        }

        return $data;
    }

    public function create(array $data): int
    {
        $errors = [];

        if (empty($data['company_id'])) {
            $errors['company_id'] = 'company_id is required';
        }

        if (empty($data['title'])) {
            $errors['title'] = 'title is required';
        }

        if (isset($data['payout_amount']) && !is_numeric($data['payout_amount'])) {
            $errors['payout_amount'] = 'payout_amount must be numeric';
        }

        if (!empty($errors)) {
            throw new ValidationException('Invalid work order data', $errors);
        }

        $allowedInitialStatuses = ['draft', 'dispatched'];

        if (!isset($data['status']) || $data['status'] === '') {
            $data['status'] = 'draft';
        } elseif (!in_array($data['status'], $allowedInitialStatuses, true)) {
            throw new ValidationException('Invalid initial status for new work order', [
                'status' => 'Must be one of: ' . implode(', ', $allowedInitialStatuses),
            ]);
        }


        return $this->repo->create($data);
    }


    public function createBulk(array $items): array
    {
        if (!is_array($items) || $items === []) {
            throw new \Exception('Payload must be a non-empty array of work orders');
        }

        $validated = [];

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                throw new \Exception("Item at index {$index} must be an object");
            }

            $validated[] = $this->validateCreate($item);
        }

        return $this->repo->createMany($validated);
    }


    public function update(int $id, array $data): bool
    {
        $existing = $this->repo->findById($id);
    
        if (!$existing) {
            throw new \Exception('Work order not found');
        }
    
        $currentStatus = $existing['status'];
    
        if (isset($data['status']) && $data['status'] !== null && $data['status'] !== '') {
            $newStatus = $data['status'];
    
            $this->assertStatusTransition($currentStatus, $newStatus);
        } else {
            if (in_array($currentStatus, ['completed', 'cancelled'], true)) {
                throw new ValidationException(
                    "Cannot update a {$currentStatus} work order",
                    ['status' => 'Completed or cancelled work orders are read-only']
                );
            }
        }
    
        $errors = [];
    
        if (array_key_exists('payout_amount', $data) && !is_numeric($data['payout_amount'])) {
            $errors['payout_amount'] = 'payout_amount must be numeric';
        }
    
        if (!empty($errors)) {
            throw new ValidationException('Invalid work order update data', $errors);
        }
    
        return $this->repo->update($id, $data);
    }
    


    public function delete(int $id): bool
    {
        if (!$this->repo->findById($id)) {
            throw new \Exception("Work order $id not found");
        }
        return $this->repo->delete($id);
    }
}
