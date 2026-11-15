<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Services\Sales\CustomerPersistenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerPersistenceService $persistence,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'customers');

        $query = Customer::query();
        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%");
            });
        }
        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }

        return Inertia::render('Sales/Customers/Index', [
            'pageTitle' => 'Customers',
            'breadcrumbs' => $this->crumbs('Directory'),
            'customers' => $query->orderBy('code')->paginate(15)->withQueryString(),
            'filters' => ['search' => $search ?? '', 'status' => $status ?? ''],
            'canManage' => $request->user()?->can('customers.manage') === true,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manageCustomers');

        return Inertia::render('Sales/Customers/Form', [
            'pageTitle' => 'New customer',
            'breadcrumbs' => $this->crumbs('New customer'),
            'customer' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageCustomers');

        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:32', 'unique:customers,code'],
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string'],
            'opening_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $customer = $this->persistence->create($data, $request->user());

        return redirect()->route('sales.customers.show', $customer)->with('success', 'Customer created.');
    }

    public function show(Request $request, Customer $customer): Response
    {
        $this->authorize('viewModule', 'customers');

        $ledger = CustomerLedgerEntry::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return Inertia::render('Sales/Customers/Show', [
            'pageTitle' => $customer->code,
            'breadcrumbs' => $this->crumbs($customer->name),
            'customer' => $customer,
            'ledger' => $ledger,
            'canManage' => $request->user()?->can('customers.manage') === true,
        ]);
    }

    public function edit(Customer $customer): Response
    {
        $this->authorize('manageCustomers');

        return Inertia::render('Sales/Customers/Form', [
            'pageTitle' => 'Edit customer',
            'breadcrumbs' => $this->crumbs('Edit'),
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorize('manageCustomers');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $this->persistence->update($customer, $data);

        return redirect()->route('sales.customers.show', $customer)->with('success', 'Customer updated.');
    }

    /** @return list<array{label: string, href?: string}> */
    private function crumbs(string $tail): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Customers', 'href' => route('erp.customers')],
            ['label' => $tail],
        ];
    }
}