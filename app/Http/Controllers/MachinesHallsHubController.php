<?php

namespace App\Http\Controllers;

use App\Support\MasterDataRegistry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MachinesHallsHubController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewModule', 'machines-halls');

        return Inertia::render('MasterData/Hub', [
            'pageTitle' => 'Machines & Halls',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Machines & Halls'],
            ],
            'title' => 'Machines & production halls',
            'description' => 'Configure shop-floor halls, machine types, and individual machines.',
            'items' => MasterDataRegistry::hubItems('machines-halls', $request->user()),
        ]);
    }
}