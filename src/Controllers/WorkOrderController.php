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
        try {
            $data = \App\Http\Request::json();

            $service = new \App\Services\WorkOrderService($this->repo);
            $id = $service->create($data);

            \App\Http\Response::json(['id' => $id], 201);
        } catch (\Exception $e) {
            \App\Http\Response::error($e->getMessage(), 400);
        }
    }


    public function update(int $id)
    {
        try {
            $data = \App\Http\Request::json();

            $service = new \App\Services\WorkOrderService($this->repo);
            $ok = $service->update($id, $data);

            \App\Http\Response::json(['success' => $ok, 'status' => 200], 0);
        } catch (\Exception $e) {
            \App\Http\Response::error($e->getMessage(), 400);
        }
    }

    public function destroy(int $id): void
    {
        try {
            $service = new \App\Services\WorkOrderService($this->repo);
            $ok = $service->delete($id);

            \App\Http\Response::json(['success' => $ok, 'status' => 200], 0);
        } catch (\Exception $e) {
            \App\Http\Response::error($e->getMessage(), 400);
        }
    }
}
