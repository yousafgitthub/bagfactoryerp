<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierHubController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewModule', 'suppliers');

        return Inertia::render('Purchases/Suppliers/Hub', [
            'pageTitle' => 'Suppliers',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Suppliers'],
            ],
            'canManage' => $request->user()?->can('suppliers.manage') === true,
        ]);
    }
}