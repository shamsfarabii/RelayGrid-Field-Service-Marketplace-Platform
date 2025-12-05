<?php

namespace App\Controllers;

use App\Repositories\CompanyRepository;
use App\Http\Request;
use App\Http\Response;

class CompanyController
{
    private CompanyRepository $repo;

    public function __construct()
    {
        $this->repo = new CompanyRepository();
    }

    public function index()
    {
        $companies = $this->repo->findAll();
        Response::json($companies);
    }

    public function show(int $id)
    {
        $company = $this->repo->findById($id);

        if (!$company) {
            return Response::error('Company not found', 404);
        }

        Response::json($company);
    }

    public function store()
    {
        try {
            $data = Request::json();

            if (empty($data['name']) || empty($data['contact_email'])) {
                throw new \Exception('name and contact_email are required');
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
                return Response::error('Nothing updated or company not found', 400);
            }

            Response::json([
                'success' => true,
                'status'  => 200,
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function destroy(int $id): void
    {
        try {
            $ok = $this->repo->delete($id);

            \App\Http\Response::json(['success' => $ok, 'status' => 200], 0);
        } catch (\Exception $e) {
            \App\Http\Response::error($e->getMessage(), 400);
        }
    }
}
