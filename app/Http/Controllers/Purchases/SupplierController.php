<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use App\Services\Purchases\SupplierPersistenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierPersistenceService $persistence,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'suppliers');

        $query = Supplier::query();
        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%");
            });
        }
        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }

        return Inertia::render('Purchases/Suppliers/Index', [
            'pageTitle' => 'Suppliers',
            'breadcrumbs' => $this->crumbs('Suppliers'),
            'suppliers' => $query->orderBy('code')->paginate(15)->withQueryString(),
            'filters' => ['search' => $search ?? '', 'status' => $status ?? ''],
            'canManage' => $request->user()?->can('suppliers.manage') === true,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manageSuppliers');

        return Inertia::render('Purchases/Suppliers/Form', [
            'pageTitle' => 'New supplier',
            'breadcrumbs' => $this->crumbs('New supplier'),
            'supplier' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageSuppliers');

        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:32', 'unique:suppliers,code'],
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string'],
            'opening_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $supplier = $this->persistence->create($data, $request->user());

        return redirect()->route('purchases.suppliers.show', $supplier)->with('success', 'Supplier created.');
    }

    public function show(Request $request, Supplier $supplier): Response
    {
        $this->authorize('viewModule', 'suppliers');

        $ledger = SupplierLedgerEntry::query()
            ->where('supplier_id', $supplier->id)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('Purchases/Suppliers/Show', [
            'pageTitle' => $supplier->name,
            'breadcrumbs' => $this->crumbs($supplier->name),
            'supplier' => $supplier,
            'ledger' => $ledger,
            'canManage' => $request->user()?->can('suppliers.manage') === true,
        ]);
    }

    public function edit(Supplier $supplier): Response
    {
        $this->authorize('manageSuppliers');

        return Inertia::render('Purchases/Suppliers/Form', [
            'pageTitle' => 'Edit supplier',
            'breadcrumbs' => $this->crumbs('Edit supplier'),
            'supplier' => $supplier,
        ]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorize('manageSuppliers');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $this->persistence->update($supplier, $data);

        return redirect()->route('purchases.suppliers.show', $supplier)->with('success', 'Supplier updated.');
    }

    /** @return list<array{label: string, href?: string}> */
    private function crumbs(string $tail): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Suppliers', 'href' => route('erp.suppliers')],
            ['label' => $tail],
        ];
    }
}