<?php

namespace App\Repositories;

interface TechnicianRepositoryInterface
{
    public function findAll(): array;

    public function findById(int $id): ?array;

    public function create(array $data): int;

    public function update(int $id, array $data): bool;
}
