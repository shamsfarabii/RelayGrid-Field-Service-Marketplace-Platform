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
