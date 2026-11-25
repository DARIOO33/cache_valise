<?php

namespace App\Http\Controllers;

use App\Http\Requests\MouvementCaisseRequest;
use App\Models\MouvementCaisse;
use App\Services\CashService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class CaisseController extends Controller
{
    public function index(Request $request, CashService $caisse): View
    {
        $filtres = $request->only(['du', 'au', 'type']);

        $mouvements = MouvementCaisse::with(['reglement.vente', 'paiementCharge.charge'])
            ->when($filtres['du'] ?? null, fn (Builder $q, $du) => $q->whereDate('date_mouvement', '>=', $du))
            ->when($filtres['au'] ?? null, fn (Builder $q, $au) => $q->whereDate('date_mouvement', '<=', $au))
            ->when($filtres['type'] ?? null, fn (Builder $q, $t) => $q->where('type', $t))
            ->orderBy('date_mouvement')
            ->orderBy('id')
            ->get();

        // Running balance, computed over the journal in chronological order.
        $cumul = 0.0;
        $mouvements->each(function (MouvementCaisse $m) use (&$cumul) {
            $cumul += $m->sens === 'entree' ? (float) $m->montant : -(float) $m->montant;
            $m->solde_cumule = round($cumul, 3);
        });

        return view('caisse.index', [
            'mouvements' => $mouvements->reverse()->values(),
            'solde' => $caisse->solde(),
            'totalEntrees' => (float) $mouvements->where('sens', 'entree')->sum('montant'),
            'totalSorties' => (float) $mouvements->where('sens', 'sortie')->sum('montant'),
            'filtres' => $filtres,
        ]);
    }

    public function create(): View
    {
        return view('caisse.create', [
            'soldeInitialExiste' => MouvementCaisse::where('type', 'solde_initial')->exists(),
        ]);
    }

    public function store(MouvementCaisseRequest $request, CashService $caisse): RedirectResponse
    {
        try {
            $caisse->mouvementManuel($request->validated());
        } catch (RuntimeException $e) {
            return redirect()->route('caisse.create')->withErrors(['mouvement' => $e->getMessage()])->withInput();
        }

        return redirect()->route('caisse.index')->with('status', 'Mouvement enregistre.');
    }

    public function annulation(MouvementCaisse $mouvement, CashService $caisse): RedirectResponse
    {
        try {
            $caisse->annulerMouvement($mouvement);
        } catch (RuntimeException $e) {
            return redirect()->route('caisse.index')->withErrors(['annulation' => $e->getMessage()]);
        }

        return redirect()->route('caisse.index')->with('status', 'Contre-ecriture enregistree.');
    }
}
