<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\GlJournalEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JournalEntryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'accounting');

        $entries = GlJournalEntry::query()
            ->withCount('lines')
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('Accounting/Journals/Index', [
            'pageTitle' => 'Journal entries',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Accounting', 'href' => route('erp.accounting')],
                ['label' => 'Journals'],
            ],
            'entries' => $entries,
        ]);
    }

    public function show(GlJournalEntry $journalEntry): Response
    {
        $this->authorize('viewModule', 'accounting');

        $journalEntry->load(['lines.account', 'reversalOf']);

        return Inertia::render('Accounting/Journals/Show', [
            'pageTitle' => $journalEntry->entry_number,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Accounting', 'href' => route('erp.accounting')],
                ['label' => 'Journals', 'href' => route('accounting.journals.index')],
                ['label' => $journalEntry->entry_number],
            ],
            'entry' => $journalEntry,
        ]);
    }
}