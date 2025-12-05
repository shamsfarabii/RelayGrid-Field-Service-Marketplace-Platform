<?php

namespace App\Controllers;

use App\Repositories\CompanyRepository;
use App\Http\Request;
use App\Http\Response;
use App\Http\Auth;


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

        Response::success([
            'items' => $companies,
            'count' => count($companies),
        ]);
    }

    public function show(int $id)
    {
        $company = $this->repo->findById($id);

        if (!$company) {
            return Response::error('Company not found', 404);
        }

        Response::success($company);
    }

    public function store()
    {
        try {
            $user = Auth::requireRole(['admin', 'company']);

            $data = Request::json();

            if (empty($data['name']) || empty($data['contact_email'])) {
                throw new \Exception('name and contact_email are required');
            }

            if ($user['role'] === 'company') {
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
                return Response::error('Nothing updated or company not found', 400);
            }

            Response::success(['updated' => true], 200);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function destroy(int $id): void
    {
        try {
            $ok = $this->repo->delete($id);

            if (!$ok) {
                Response::error('Company not found', 404);
                return;
            }

            Response::success(['deleted' => true], 200);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
