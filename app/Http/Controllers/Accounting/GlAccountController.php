<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\GlAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GlAccountController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'accounting');

        $accounts = GlAccount::query()->orderBy('code')->get();

        return Inertia::render('Accounting/GlAccounts/Index', [
            'pageTitle' => 'Chart of accounts',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Accounting', 'href' => route('erp.accounting')],
                ['label' => 'Chart of accounts'],
            ],
            'accounts' => $accounts,
            'accountTypes' => config('accounting.gl_account_types'),
        ]);
    }
}