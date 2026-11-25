<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChargeRequest;
use App\Models\Charge;
use App\Models\Fournisseur;
use App\Models\Lot;
use App\Services\CashService;
use App\Services\SequenceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChargeController extends Controller
{
    public function index(Request $request): View
    {
        $filtres = $request->only(['lot_id', 'categorie', 'du', 'au']);

        $requete = Charge::with(['lot.modele', 'fournisseur'])
            ->when($filtres['lot_id'] ?? null, fn (Builder $q, $lot) => $q->where('lot_id', $lot))
            ->when($filtres['categorie'] ?? null, fn (Builder $q, $c) => $q->where('categorie', $c))
            ->when($filtres['du'] ?? null, fn (Builder $q, $du) => $q->whereDate('date_charge', '>=', $du))
            ->when($filtres['au'] ?? null, fn (Builder $q, $au) => $q->whereDate('date_charge', '<=', $au));

        return view('charges.index', [
            // The total reflects the filters, which is the point of filtering.
            'total' => (float) (clone $requete)->sum('montant'),
            'charges' => $requete->orderByDesc('date_charge')->orderByDesc('id')->paginate(20)->withQueryString(),
            'lots' => Lot::orderByDesc('date_production')->get(),
            'filtres' => $filtres,
        ]);
    }

    public function create(Request $request): View
    {
        return view('charges.create', [
            'charge' => new Charge([
                'date_charge' => now()->toDateString(),
                'lot_id' => $request->integer('lot_id') ?: null,
                'categorie' => 'tissu',
                'quantite' => 1,
            ]),
            ...$this->listes(),
        ]);
    }

    public function store(ChargeRequest $request, SequenceService $sequences): RedirectResponse
    {
        $charge = Charge::create([
            ...$request->validated(),
            'numero_piece' => $sequences->suivant('piece'),
        ]);

        return redirect()
            ->route('lots.show', $charge->lot_id)
            ->with('status', "Charge {$charge->numero_piece} enregistree.");
    }

    public function show(Charge $charge, CashService $caisse): View
    {
        $charge->load(['lot.modele', 'fournisseur', 'paiements.mouvementCaisse']);

        return view('charges.show', [
            'charge' => $charge,
            'resteAPayer' => $caisse->resteAPayer($charge),
        ]);
    }

    public function edit(Charge $charge): View
    {
        return view('charges.edit', ['charge' => $charge, ...$this->listes()]);
    }

    public function update(ChargeRequest $request, Charge $charge): RedirectResponse
    {
        $charge->update($request->validated());

        return redirect()
            ->route('lots.show', $charge->lot_id)
            ->with('status', "Charge {$charge->numero_piece} modifiee.");
    }

    /**
     * A charge can be removed only while nothing has been paid against it.
     * Once a payment exists the charge is corrected, never deleted, so the
     * cost history and the caisse stay consistent.
     */
    public function destroy(Charge $charge): RedirectResponse
    {
        if ($charge->paiements()->exists()) {
            return redirect()
                ->route('lots.show', $charge->lot_id)
                ->withErrors(['suppression' => "La charge {$charge->numero_piece} porte des paiements : corrigez-la au lieu de la supprimer."]);
        }

        $lotId = $charge->lot_id;
        $piece = $charge->numero_piece;
        $charge->delete();

        return redirect()
            ->route('lots.show', $lotId)
            ->with('status', "Charge {$piece} supprimee.");
    }

    private function listes(): array
    {
        return [
            'lots' => Lot::with('modele')->where('etat', '!=', 'annule')->orderByDesc('date_production')->get(),
            'fournisseurs' => Fournisseur::orderBy('nom')->get(),
        ];
    }
}
