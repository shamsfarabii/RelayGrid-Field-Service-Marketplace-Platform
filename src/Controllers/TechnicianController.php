<?php

namespace App\Controllers;

use App\Repositories\TechnicianRepository;
use App\Http\Request;
use App\Http\Response;
use App\Http\Auth;


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

        Response::success([
            'items' => $technicians,
            'count' => count($technicians),
        ]);
    }

    public function show(int $id)
    {
        $tech = $this->repo->findById($id);

        if (!$tech) {
            return Response::error('Technician not found', 404);
        }

        Response::success($tech);
    }

    public function store()
    {
        try {
            $user = Auth::requireRole(['admin', 'technician']);

            $data = Request::json();

            if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email'])) {
                throw new \Exception('first_name, last_name, and email are required');
            }

            if ($user['role'] === 'technician') {
                $existing = $this->repo->findByUserId((int)$user['id']);
                if ($existing) {
                    throw new \Exception('Technician profile already exists for this user');
                }

                $data['user_id'] = $user['id'];
            } else {
                $data['user_id'] = $data['user_id'] ?? null;
            }

            $id = $this->repo->create($data);

            Response::success(['id' => $id], 201);
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

            Response::success(['updated' => true], 200);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
