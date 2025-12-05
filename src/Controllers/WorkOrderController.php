<?php

namespace App\Controllers;

use App\Repositories\WorkOrderRepository;
use App\Http\Response;

class WorkOrderController
{
    private WorkOrderRepository $repo;

    public function __construct()
    {
        $this->repo = new WorkOrderRepository();
    }

    public function index()
    {
        // Read query params
        $page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage  = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 20;

        $filters = [
            'company_id' => $_GET['company_id'] ?? null,
            'status'     => $_GET['status'] ?? null,
            'start_date' => $_GET['start'] ?? null,
            'end_date'   => $_GET['end'] ?? null,
        ];

        $items = $this->repo->findByFilters($filters, $page, $perPage);
        $total = $this->repo->countByFilters($filters);

        $totalPages = (int)ceil($total / max(1, $perPage));

        \App\Http\Response::json([
            'success'    => true,
            'status'     => 200,
            'page'       => $page,
            'perPage'    => $perPage,
            'total'      => $total,
            'totalPages' => $totalPages,
            'count'      => count($items),
            'items'      => $items,
        ]);
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

    public function storeBulk()
    {
        try {
            $data = \App\Http\Request::json();

            $service = new \App\Services\WorkOrderService($this->repo);
            $ids = $service->createBulk($data);

            \App\Http\Response::json([
                'success' => true,
                'status'  => 201,
                'count'   => count($ids),
                'ids'     => $ids,
            ], 201);
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
