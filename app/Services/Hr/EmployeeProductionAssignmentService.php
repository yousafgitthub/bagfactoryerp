<?php

namespace App\Services\Hr;

use App\Models\EmployeeProductionAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmployeeProductionAssignmentService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): EmployeeProductionAssignment
    {
        $this->assertTargetPresent($data);

        $data['created_by'] = $actor?->id;

        return DB::transaction(function () use ($data) {
            return EmployeeProductionAssignment::query()
                ->create($data)
                ->load(['employee', 'department', 'machine', 'danaBatch', 'bagProductionBatch', 'bagProductionOrder']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmployeeProductionAssignment $assignment, array $data): EmployeeProductionAssignment
    {
        $this->assertTargetPresent(array_merge($assignment->toArray(), $data));

        return DB::transaction(function () use ($assignment, $data) {
            $assignment->update($data);

            return $assignment->fresh([
                'employee', 'department', 'machine', 'danaBatch', 'bagProductionBatch', 'bagProductionOrder',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertTargetPresent(array $data): void
    {
        $type = $data['assignment_type'] ?? '';

        $required = match ($type) {
            'department' => filled($data['department_id'] ?? null),
            'dana_machine', 'bag_machine' => filled($data['machine_id'] ?? null),
            'dana_batch' => filled($data['dana_batch_id'] ?? null),
            'bag_batch' => filled($data['bag_production_batch_id'] ?? null),
            'bag_order' => filled($data['bag_production_order_id'] ?? null),
            default => false,
        };

        if (! $required) {
            throw new HrException('Select a valid target for the production assignment type.');
        }
    }
}