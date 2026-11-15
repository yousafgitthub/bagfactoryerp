<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\TreasuryAccount;
use App\Models\TreasuryMovement;
use App\Services\Accounting\AccountingException;
use App\Services\Accounting\TreasuryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TreasuryAccountController extends Controller
{
    public function __construct(private readonly TreasuryService $treasury)
    {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'accounting');

        $accounts = TreasuryAccount::query()->with('glAccount')->orderBy('code')->get();
        $movements = TreasuryMovement::query()
            ->with('treasuryAccount')
            ->orderByDesc('movement_date')
            ->limit(50)
            ->get();

        return Inertia::render('Accounting/Treasury/Index', [
            'pageTitle' => 'Cash & bank',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Expenses', 'href' => route('erp.expenses')],
                ['label' => 'Treasury'],
            ],
            'accounts' => $accounts,
            'movements' => $movements,
            'movementTypes' => config('accounting.treasury_movement_types'),
            'canManage' => $request->user()?->can('accounting.manage') === true,
        ]);
    }

    public function storeTransfer(Request $request): RedirectResponse
    {
        $this->authorize('manageAccounting');

        $data = $request->validate([
            'from_treasury_account_id' => ['required', 'exists:treasury_accounts,id'],
            'to_treasury_account_id' => ['required', 'different:from_treasury_account_id', 'exists:treasury_accounts,id'],
            'movement_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
        ]);

        $from = TreasuryAccount::query()->findOrFail($data['from_treasury_account_id']);
        $to = TreasuryAccount::query()->findOrFail($data['to_treasury_account_id']);

        try {
            $this->treasury->recordCashMovement(
                account: $from,
                movementType: 'transfer',
                movementDate: $data['movement_date'],
                amount: (float) $data['amount'],
                description: $data['notes'] ?? 'Treasury transfer',
                sourceType: 'treasury_transfer',
                sourceId: null,
                actor: $request->user(),
                counterAccount: $to,
            );
        } catch (AccountingException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('status', 'Transfer recorded.');
    }
}