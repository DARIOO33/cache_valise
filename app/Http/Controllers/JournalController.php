<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only view of the audit trail required by section 4.
 * There is no route to edit or delete an entry, by design.
 */
class JournalController extends Controller
{
    public function index(Request $request): View
    {
        $filtres = $request->only(['action', 'type', 'du', 'au']);

        $entrees = AuditLog::with('user')
            ->when($filtres['action'] ?? null, fn (Builder $q, $a) => $q->where('action', $a))
            ->when($filtres['type'] ?? null, fn (Builder $q, $t) => $q->where('auditable_type', $t))
            ->when($filtres['du'] ?? null, fn (Builder $q, $du) => $q->whereDate('created_at', '>=', $du))
            ->when($filtres['au'] ?? null, fn (Builder $q, $au) => $q->whereDate('created_at', '<=', $au))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('journal.index', [
            'entrees' => $entrees,
            'filtres' => $filtres,
            'actions' => AuditLog::select('action')->distinct()->orderBy('action')->pluck('action'),
            'types' => AuditLog::select('auditable_type')->distinct()->orderBy('auditable_type')->pluck('auditable_type'),
        ]);
    }
}
