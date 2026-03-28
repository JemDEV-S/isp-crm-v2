<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\FieldOps\app\DTOs\ValidationResult;
use Modules\FieldOps\app\Enums\ExceptionType;
use Modules\FieldOps\app\Models\WorkOrder;
use Modules\FieldOps\app\Models\WorkOrderException;
use Modules\FieldOps\app\Models\WorkOrderValidation;

class ValidationService
{
    public function validate(int $workOrderId, array $criteria): ValidationResult
    {
        $workOrder = WorkOrder::with(['checklistResponse', 'photos', 'technicianLocations'])->findOrFail($workOrderId);

        $criteriaChecked = [
            'checklist_completed' => $workOrder->checklistResponse?->isCompleted() ?? false,
            'required_photos' => $this->hasRequiredPhotos($workOrder),
            'technician_location' => $workOrder->technicianLocations()->exists(),
            'work_started' => $workOrder->isStarted(),
            'work_completed' => $workOrder->isCompleted(),
        ];

        $issues = [];

        foreach ($criteriaChecked as $key => $passed) {
            if (!$passed) {
                $issues[] = $key;
            }
        }

        $extraRequired = $criteria['required'] ?? [];
        foreach ($extraRequired as $criterion) {
            if (!($criteriaChecked[$criterion] ?? false)) {
                $issues[] = $criterion;
            }
        }

        $issues = array_values(array_unique($issues));

        return new ValidationResult(
            passed: empty($issues),
            status: empty($issues) ? 'approved' : 'rejected',
            criteriaChecked: $criteriaChecked,
            issues: $issues,
        );
    }

    public function approve(int $workOrderId, array $criteria = [], ?int $validatorId = null): ValidationResult
    {
        $result = $this->validate($workOrderId, $criteria);

        WorkOrderValidation::updateOrCreate(
            ['work_order_id' => $workOrderId],
            [
                'validator_id' => $validatorId,
                'status' => $result->passed ? 'approved' : 'rejected',
                'criteria_checked' => $result->criteriaChecked,
                'observations' => $result->issues,
                'validated_at' => now(),
            ]
        );

        if (!$result->passed) {
            $this->registerValidationIssues($workOrderId, $result->issues);
        }

        return $result;
    }

    public function reject(int $workOrderId, array $observations): void
    {
        DB::transaction(function () use ($workOrderId, $observations) {
            WorkOrderValidation::updateOrCreate(
                ['work_order_id' => $workOrderId],
                [
                    'validator_id' => auth()->id(),
                    'status' => 'rejected',
                    'criteria_checked' => [],
                    'observations' => $observations,
                    'validated_at' => now(),
                ]
            );

            $this->registerValidationIssues($workOrderId, $observations);
        });
    }

    public function requestCorrection(int $workOrderId, array $issues): void
    {
        WorkOrderValidation::updateOrCreate(
            ['work_order_id' => $workOrderId],
            [
                'validator_id' => auth()->id(),
                'status' => 'correction_requested',
                'criteria_checked' => [],
                'observations' => $issues,
                'validated_at' => now(),
            ]
        );

        $this->registerValidationIssues($workOrderId, $issues);
    }

    protected function hasRequiredPhotos(WorkOrder $workOrder): bool
    {
        $types = $workOrder->photos->pluck('type')->map(fn ($type) => $type?->value)->filter()->all();

        return in_array('before', $types, true) && in_array('after', $types, true);
    }

    protected function registerValidationIssues(int $workOrderId, array $issues): void
    {
        foreach ($issues as $issue) {
            WorkOrderException::create([
                'work_order_id' => $workOrderId,
                'exception_type' => ExceptionType::FAILED_VALIDATION,
                'causal_code' => is_string($issue) ? $issue : 'validation_issue',
                'description' => is_string($issue) ? $issue : json_encode($issue),
            ]);
        }
    }
}
