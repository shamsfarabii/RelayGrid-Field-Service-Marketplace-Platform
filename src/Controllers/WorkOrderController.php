<?php

namespace App\Controllers;

use App\Repositories\WorkOrderRepository;
use App\Services\WorkOrderService;
use App\Http\Response;
use App\Http\Auth;
use App\Http\Request;
use App\Repositories\CompanyRepository;
use App\Exceptions\ValidationException;



class WorkOrderController
{
    private WorkOrderRepository $repo;
    private WorkOrderService $service;
    private CompanyRepository $companyRepo;

    public function __construct()
    {
        $this->repo        = new WorkOrderRepository();
        $this->service     = new WorkOrderService($this->repo);
        $this->companyRepo = new CompanyRepository();
    }

    public function index()
    {
        $user = Auth::user();

        $page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 20;

        $filters = [
            'company_id' => $_GET['company_id'] ?? null,
            'status'     => $_GET['status'] ?? null,
            'start_date' => $_GET['start'] ?? null,
            'end_date'   => $_GET['end'] ?? null,
        ];

        if ($user && $user['role'] === 'company') {
            $company = $this->companyRepo->findByUserId((int)$user['id']);

            if (!$company) {
                return Response::error('No company associated with this user', 400);
            }

            $filters['company_id'] = (int)$company['id'];
        }

        $items      = $this->repo->findByFilters($filters, $page, $perPage);
        $total      = $this->repo->countByFilters($filters);
        $totalPages = (int)ceil($total / max(1, $perPage));

        Response::success([
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
        $workOrder = $this->repo->findById($id);

        if (!$workOrder) {
            return Response::error('Work order not found', 404);
        }

        $user = Auth::user();

        if ($user && $user['role'] === 'company') {
            $company = $this->companyRepo->findByUserId((int)$user['id']);

            if (!$company || (int)$company['id'] !== (int)$workOrder['company_id']) {
                return Response::error('You do not have permission to view this work order', 403);
            }
        }

        Response::success($workOrder);
    }


    public function store()
    {
        try {
            $user = Auth::requireRole(['admin', 'company']);

            $data = Request::json();

            if ($user['role'] === 'company') {
                $company = $this->companyRepo->findByUserId((int)$user['id']);

                if (!$company) {
                    throw new \Exception('No company associated with this user');
                }

                $data['company_id'] = (int)$company['id'];
            } else {
                if (empty($data['company_id'])) {
                    throw new ValidationException('company_id is required for admin-created work orders', [
                        'company_id' => 'Required for admin-created work orders',
                    ]);
                }

                $company = $this->companyRepo->findById((int)$data['company_id']);
                if (!$company) {
                    throw new ValidationException('Invalid company_id', [
                        'company_id' => 'company_id does not reference a valid company',
                    ]);
                }
            }

            $id = $this->service->create($data);

            Response::success(['id' => $id], 201);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422, $e->getErrors());
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }



    public function storeBulk()
    {
        try {
            $user = Auth::requireRole(['admin', 'company']);

            $data = Request::json();

            if (!is_array($data) || $data === []) {
                throw new ValidationException('Payload must be a non-empty array of work orders', [
                    'root' => 'Expected a non-empty array',
                ]);
            }

            if ($user['role'] === 'company') {
                $company = $this->companyRepo->findByUserId((int)$user['id']);
                if (!$company) {
                    throw new \Exception('No company associated with this user');
                }

                $companyId = (int)$company['id'];

                foreach ($data as $index => &$item) {
                    if (!is_array($item)) {
                        throw new ValidationException("Item at index {$index} must be an object", [
                            (string)$index => 'Expected an object',
                        ]);
                    }

                    $item['company_id'] = $companyId;
                }
                unset($item);
            } else {
                $companyCache = [];

                foreach ($data as $index => &$item) {
                    if (!is_array($item)) {
                        throw new ValidationException("Item at index {$index} must be an object", [
                            (string)$index => 'Expected an object',
                        ]);
                    }

                    if (empty($item['company_id'])) {
                        throw new ValidationException("company_id is required for item at index {$index}", [
                            "items.{$index}.company_id" => 'Required',
                        ]);
                    }

                    $cid = (int)$item['company_id'];

                    if (!isset($companyCache[$cid])) {
                        $company = $this->companyRepo->findById($cid);
                        if (!$company) {
                            throw new ValidationException("Invalid company_id {$cid} for item at index {$index}", [
                                "items.{$index}.company_id" => 'Invalid company_id',
                            ]);
                        }
                        $companyCache[$cid] = true;
                    }
                }
                unset($item);
            }

            $ids = $this->service->createBulk($data);

            Response::success([
                'count' => count($ids),
                'ids'   => $ids,
            ], 201);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422, $e->getErrors());
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }


    public function update(int $id)
    {
        try {
            $user = Auth::requireRole(['admin', 'company']);

            $existing = $this->repo->findById($id);
            if (!$existing) {
                return Response::error('Work order not found', 404);
            }

            if ($user['role'] === 'company') {
                $company = $this->companyRepo->findByUserId((int)$user['id']);
                if (!$company || (int)$company['id'] !== (int)$existing['company_id']) {
                    return Response::error('You do not have permission to update this work order', 403);
                }
            }

            $data = Request::json();

            $ok = $this->service->update($id, $data);

            if (!$ok) {
                return Response::error('Work order not updated', 400);
            }

            Response::success(['updated' => true], 200);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422, $e->getErrors());
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }


    public function destroy(int $id): void
    {
        try {
            $user = Auth::requireRole(['admin', 'company']);

            $existing = $this->repo->findById($id);
            if (!$existing) {
                Response::error('Work order not found', 404);
                return;
            }

            if ($user['role'] === 'company') {
                $company = $this->companyRepo->findByUserId((int)$user['id']);
                if (!$company || (int)$company['id'] !== (int)$existing['company_id']) {
                    Response::error('You do not have permission to delete this work order', 403);
                    return;
                }
            }

            $ok = $this->service->delete($id);

            if (!$ok) {
                Response::error('Work order not deleted', 400);
                return;
            }

            Response::success(['deleted' => true], 200);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
