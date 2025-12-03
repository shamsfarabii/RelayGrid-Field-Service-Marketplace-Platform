<?php

namespace App\Controllers;

use App\Repositories\WorkOrderRepository;

class WorkOrderController
{
    private WorkOrderRepository $repo;

    public function __construct()
    {
        $this->repo = new WorkOrderRepository();
    }

    public function index()
    {
        echo json_encode($this->repo->findAll());
    }

    public function show(int $id)
    {
        echo json_encode($this->repo->findById($id));
    }

    public function store()
    {
        echo json_encode(['message' => 'POST not implemented yet']);
    }

    public function update(int $id)
    {
        echo json_encode(['message' => 'PUT not implemented yet']);
    }
}
