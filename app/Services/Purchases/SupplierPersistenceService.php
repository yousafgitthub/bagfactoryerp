<?php

namespace App\Services\Purchases;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SupplierPersistenceService
{
    public function __construct(
        private readonly PurchaseDocumentNumberGenerator $numbers,
        private readonly SupplierLedgerService $ledger,
    ) {
    }

    public function create(array $data, User $actor): Supplier
    {
        return DB::transaction(function () use ($data, $actor) {
            $supplier = Supplier::query()->create([
                'code' => $data['code'] ?? $this->numbers->next('supplier'),
                'name' => $data['name'],
                'contact_person' => $data['contact_person'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'opening_balance' => $data['opening_balance'] ?? 0,
                'current_balance' => 0,
                'notes' => $data['notes'] ?? null,
                'status' => $data['status'] ?? 'active',
                'created_by' => $actor->id,
            ]);

            $this->ledger->recordOpening($supplier->fresh(), $actor);

            return $supplier->fresh();
        });
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        if (! $supplier->isActive() && ($data['status'] ?? $supplier->status) === 'active') {
            // allow reactivation
        }

        $supplier->update([
            'name' => $data['name'],
            'contact_person' => $data['contact_person'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? $supplier->status,
        ]);

        return $supplier->fresh();
    }
}