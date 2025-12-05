<?php

namespace App\Services;

use App\Repositories\WorkOrderRepositoryInterface;

class WorkOrderService
{
    private WorkOrderRepositoryInterface $repo;

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
        $data = $this->validateCreate($data);
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
        if (!$this->repo->findById($id)) {
            throw new \Exception("Work order $id not found");
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
