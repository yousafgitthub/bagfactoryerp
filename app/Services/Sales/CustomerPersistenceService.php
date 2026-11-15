<?php

namespace App\Services\Sales;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CustomerPersistenceService
{
    public function __construct(
        private readonly SalesDocumentNumberGenerator $numbers,
        private readonly CustomerLedgerService $ledger,
    ) {
    }

    public function create(array $data, User $actor): Customer
    {
        return DB::transaction(function () use ($data, $actor) {
            $customer = Customer::query()->create([
                'code' => $data['code'] ?? $this->numbers->next('customer'),
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

            $this->ledger->recordOpening($customer->fresh(), $actor);

            return $customer->fresh();
        });
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update([
            'name' => $data['name'],
            'contact_person' => $data['contact_person'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? $customer->status,
        ]);

        return $customer->fresh();
    }
}