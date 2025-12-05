<?php

namespace App\Controllers;

use App\Repositories\TechnicianRepository;
use App\Http\Request;
use App\Http\Response;

class TechnicianController
{
    private TechnicianRepository $repo;

    public function __construct()
    {
        $this->repo = new TechnicianRepository();
    }

    public function index()
    {
        $technicians = $this->repo->findAll();
        Response::json($technicians);
    }

    public function show(int $id)
    {
        $tech = $this->repo->findById($id);

        if (!$tech) {
            return Response::error('Technician not found', 404);
        }

        Response::json($tech);
    }

    public function store()
    {
        try {
            $data = Request::json();

            if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email'])) {
                throw new \Exception('first_name, last_name, and email are required');
            }

            $id = $this->repo->create($data);

            Response::json([
                'success' => true,
                'status'  => 201,
                'id'      => $id,
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function update(int $id)
    {
        try {
            $data = Request::json();

            $ok = $this->repo->update($id, $data);

            if (!$ok) {
                return Response::error('Nothing updated or technician not found', 400);
            }

            Response::json([
                'success' => true,
                'status'  => 200,
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
