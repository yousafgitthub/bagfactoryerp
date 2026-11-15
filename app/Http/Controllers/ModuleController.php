<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModuleController extends Controller
{
    public function __invoke(Request $request, string $module): Response
    {
        $modules = config('erp.modules', []);

        abort_unless(isset($modules[$module]), 404);

        $this->authorize('viewModule', $module);

        $label = $modules[$module]['label'];

        return Inertia::render('ModulePlaceholder', [
            'pageTitle' => $label,
            'module' => $module,
            'moduleLabel' => $label,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => $label],
            ],
        ]);
    }
}