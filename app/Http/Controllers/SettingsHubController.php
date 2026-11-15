<?php

namespace App\Http\Controllers;

use App\Support\MasterDataRegistry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsHubController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewModule', 'settings');

        return Inertia::render('MasterData/Hub', [
            'pageTitle' => 'Settings',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Settings'],
            ],
            'title' => 'Factory master data',
            'description' => 'Units, categories, colors, warehouses, HR structure, and expense categories.',
            'items' => MasterDataRegistry::hubItems('settings', $request->user()),
        ]);
    }
}