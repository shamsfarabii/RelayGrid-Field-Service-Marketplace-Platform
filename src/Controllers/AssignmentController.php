<?php

namespace App\Controllers;

use App\Http\Auth;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\WorkOrderAssignmentRepository;
use App\Repositories\WorkOrderRepository;
use App\Repositories\CompanyRepository;
use App\Services\WorkOrderAssignmentService;
use App\Repositories\TechnicianRepository;
use App\Services\WorkOrderService;



class AssignmentController
{
    private WorkOrderAssignmentRepository $assignmentRepo;
    private WorkOrderRepository $workOrderRepo;
    private CompanyRepository $companyRepo;
    private TechnicianRepository $technicianRepo;
    private WorkOrderService $workOrderService;
    private WorkOrderAssignmentService $service;

    public function __construct()
    {
        $this->assignmentRepo   = new WorkOrderAssignmentRepository();
        $this->workOrderRepo    = new WorkOrderRepository();
        $this->companyRepo      = new CompanyRepository();
        $this->technicianRepo   = new TechnicianRepository();
        $this->workOrderService = new WorkOrderService($this->workOrderRepo);
        $this->service          = new WorkOrderAssignmentService(
            $this->assignmentRepo,
            $this->workOrderRepo,
            $this->workOrderService
        );
    }

    public function storeForWorkOrder(int $workOrderId)
    {
        try {
            $user = Auth::requireRole(['admin', 'company']);

            $workOrder = $this->workOrderRepo->findById($workOrderId);
            if (!$workOrder) {
                return Response::error('Work order not found', 404);
            }

            if ($user['role'] === 'company') {
                $company = $this->companyRepo->findByUserId((int)$user['id']);
                if (!$company || (int)$company['id'] !== (int)$workOrder['company_id']) {
                    return Response::error('You do not have permission to assign this work order', 403);
                }
            }

            $body = Request::json();
            $technicianId = isset($body['technician_id']) ? (int)$body['technician_id'] : 0;

            if ($technicianId <= 0) {
                throw new \Exception('technician_id is required and must be a positive integer');
            }

            $id = $this->service->inviteTechnician($workOrderId, $technicianId);

            Response::success(['id' => $id], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function updateStatus(int $id)
    {
        try {
            $user = Auth::requireRole(['admin', 'technician']);

            $body = Request::json();
            $status = $body['status'] ?? '';

            $allowed = ['accepted', 'rejected', 'completed'];

            if (!in_array($status, $allowed, true)) {
                throw new \Exception('status must be one of: ' . implode(', ', $allowed));
            }

            $assignment = $this->assignmentRepo->findById($id);
            if (!$assignment) {
                return Response::error('Assignment not found', 404);
            }

            if ($user['role'] === 'technician') {
                $technician = $this->technicianRepo->findByUserId((int)$user['id']);

                if (!$technician) {
                    return Response::error('No technician profile associated with this user', 400);
                }

                if ((int)$technician['id'] !== (int)$assignment['technician_id']) {
                    return Response::error('You do not have permission to update this assignment', 403);
                }
            }

            $ok = $this->service->changeStatus($id, $status);

            if (!$ok) {
                return Response::error('Assignment status not updated', 400);
            }

            Response::success(['updated' => true], 200);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function indexForWorkOrder(int $workOrderId)
    {
        try {
            $user = Auth::requireRole(['admin', 'company']);

            $workOrder = $this->workOrderRepo->findById($workOrderId);
            if (!$workOrder) {
                return Response::error('Work order not found', 404);
            }

            if ($user['role'] === 'company') {
                $company = $this->companyRepo->findByUserId((int)$user['id']);
                if (!$company || (int)$company['id'] !== (int)$workOrder['company_id']) {
                    return Response::error('You do not have permission to view assignments for this work order', 403);
                }
            }

            $assignments = $this->assignmentRepo->findByWorkOrder($workOrderId);

            Response::success([
                'workOrderId' => $workOrderId,
                'count'       => count($assignments),
                'items'       => $assignments,
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function indexForMe()
    {
        try {
            $user = Auth::requireRole(['technician']);

            $technician = $this->technicianRepo->findByUserId((int)$user['id']);
            if (!$technician) {
                return Response::error('No technician profile associated with this user', 400);
            }

            $assignments = $this->assignmentRepo->findByTechnician((int)$technician['id']);

            Response::success([
                'technicianId' => (int)$technician['id'],
                'count'        => count($assignments),
                'items'        => $assignments,
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
