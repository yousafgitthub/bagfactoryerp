<?php

namespace App\Services\Reports;

use Illuminate\Http\Request;

class ReportFilters
{
    public function __construct(
        public ?string $from = null,
        public ?string $to = null,
        public string $granularity = 'day',
        public ?string $search = null,
        public ?string $sort = null,
        public string $sortDirection = 'asc',
        public int $page = 1,
        public int $perPage = 50,
        public ?int $warehouseId = null,
        public ?int $machineId = null,
        public ?int $employeeId = null,
        public ?int $customerId = null,
        public ?int $supplierId = null,
        public ?int $glAccountId = null,
        public ?string $bucket = null,
        public ?string $asOf = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $from = $request->string('from')->toString() ?: null;
        $to = $request->string('to')->toString() ?: null;

        if (! $from && ! $to) {
            $from = now()->startOfMonth()->toDateString();
            $to = now()->toDateString();
        }

        return new self(
            from: $from,
            to: $to,
            granularity: $request->string('granularity', 'day')->toString(),
            search: $request->string('search')->toString() ?: null,
            sort: $request->string('sort')->toString() ?: null,
            sortDirection: $request->string('direction', 'asc')->toString() === 'desc' ? 'desc' : 'asc',
            page: max(1, (int) $request->input('page', 1)),
            perPage: min(200, max(10, (int) $request->input('per_page', 50))),
            warehouseId: $request->integer('warehouse_id') ?: null,
            machineId: $request->integer('machine_id') ?: null,
            employeeId: $request->integer('employee_id') ?: null,
            customerId: $request->integer('customer_id') ?: null,
            supplierId: $request->integer('supplier_id') ?: null,
            glAccountId: $request->integer('gl_account_id') ?: null,
            bucket: $request->string('bucket')->toString() ?: null,
            asOf: $request->string('as_of')->toString() ?: ($to ?? now()->toDateString()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'granularity' => $this->granularity,
            'search' => $this->search,
            'sort' => $this->sort,
            'direction' => $this->sortDirection,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'warehouse_id' => $this->warehouseId,
            'machine_id' => $this->machineId,
            'employee_id' => $this->employeeId,
            'customer_id' => $this->customerId,
            'supplier_id' => $this->supplierId,
            'gl_account_id' => $this->glAccountId,
            'bucket' => $this->bucket,
            'as_of' => $this->asOf,
        ];
    }
}