<?php

namespace App\Services;

use App\Repositories\WorkOrderAssignmentRepositoryInterface;
use App\Repositories\WorkOrderRepository;

class WorkOrderAssignmentService
{
    private array $allowedStatusTransitions = [
        'pending'  => ['accepted', 'rejected'],
        'invited'  => ['pending', 'accepted', 'rejected'],
        'accepted' => ['completed', 'rejected'],
        'rejected' => [],
        'completed' => [],
    ];

    public function __construct(
        private WorkOrderAssignmentRepositoryInterface $assignments,
        private WorkOrderRepository $workOrders,
        private WorkOrderService $workOrderService
    ) {}

    public function inviteTechnician(int $workOrderId, int $technicianId): int
    {
        $workOrder = $this->workOrders->findById($workOrderId);
        if (!$workOrder) {
            throw new \Exception('Work order not found');
        }

        if (in_array($workOrder['status'], ['completed', 'cancelled'], true)) {
            throw new \Exception('Cannot create assignment for completed or cancelled work order');
        }

        $existingAssignments = $this->assignments->findByWorkOrder($workOrderId);
        foreach ($existingAssignments as $assignment) {
            if (
                (int)$assignment['technician_id'] === $technicianId &&
                !in_array($assignment['status'], ['rejected', 'completed'], true)
            ) {
                throw new \Exception('Technician already has an active assignment for this work order');
            }
        }

        return $this->assignments->create([
            'work_order_id' => $workOrderId,
            'technician_id' => $technicianId,
            'status'        => 'invited',
            'assigned_at'   => date('Y-m-d H:i:s'),
        ]);
    }


    public function changeStatus(int $assignmentId, string $newStatus): bool
    {
        $assignment = $this->assignments->findById($assignmentId);
        if (!$assignment) {
            throw new \Exception('Assignment not found');
        }

        $workOrderId = (int)$assignment['work_order_id'];
        $workOrder   = $this->workOrders->findById($workOrderId);

        if (!$workOrder) {
            throw new \Exception('Work order not found for this assignment');
        }

        if (in_array($workOrder['status'], ['completed', 'cancelled'], true)) {
            throw new \Exception('Cannot change assignment for completed or cancelled work order');
        }

        $currentStatus = $assignment['status'];

        $this->assertStatusTransition($currentStatus, $newStatus);

        if ($newStatus === 'accepted') {
            $assignments = $this->assignments->findByWorkOrder($workOrderId);

            foreach ($assignments as $other) {
                if ((int)$other['id'] === (int)$assignment['id']) {
                    continue;
                }

                if ($other['status'] === 'accepted') {
                    throw new \Exception('Another technician has already accepted this work order');
                }
            }
        }

        if ($newStatus === 'accepted') {
            if ($workOrder['status'] === 'draft') {
                $this->workOrderService->update($workOrderId, ['status' => 'dispatched']);
                $workOrder['status'] = 'dispatched';
            }
        }

        if ($newStatus === 'completed') {
            $this->workOrderService->update($workOrderId, ['status' => 'completed']);
            $workOrder['status'] = 'completed';
        }

        return $this->assignments->updateStatus($assignmentId, $newStatus);
    }


    private function assertStatusTransition(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }

        $allowed = $this->allowedStatusTransitions[$from] ?? [];

        if (!in_array($to, $allowed, true)) {
            throw new \Exception("Invalid assignment status transition from {$from} to {$to}");
        }
    }
}
