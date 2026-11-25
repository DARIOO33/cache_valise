<?php

namespace App\Http\Controllers;

use App\Http\Requests\LotRequest;
use App\Models\Lot;
use App\Models\Modele;
use App\Services\SequenceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LotController extends Controller
{
    public function index(): View
    {
        $lots = Lot::with('modele')
            ->withSum(['lignes as quantite_vendue' => $this->lignesValidees()], 'quantite')
            ->orderByDesc('date_production')
            ->orderByDesc('id')
            ->paginate(15);

        return view('lots.index', compact('lots'));
    }

    public function create(): View
    {
        return view('lots.create', [
            'lot' => new Lot([
                'date_production' => now()->toDateString(),
                'etat' => 'en_preparation',
            ]),
            'modeles' => $this->modelesActifs(),
        ]);
    }

    public function store(LotRequest $request, SequenceService $sequences): RedirectResponse
    {
        $lot = Lot::create([
            ...$request->validated(),
            'numero' => $sequences->suivant('lot'),
        ]);

        return redirect()
            ->route('lots.show', $lot)
            ->with('status', "Lot {$lot->numero} cree.");
    }

    public function show(Lot $lot): View
    {
        $lot->load('modele')
            ->loadSum(['lignes as quantite_vendue' => $this->lignesValidees()], 'quantite');

        return view('lots.show', compact('lot'));
    }

    public function edit(Lot $lot): View
    {
        return view('lots.edit', [
            'lot' => $lot,
            // The model already chosen stays selectable even if it was deactivated.
            'modeles' => $this->modelesActifs($lot->modele_id),
        ]);
    }

    public function update(LotRequest $request, Lot $lot): RedirectResponse
    {
        $lot->update($request->validated());

        return redirect()
            ->route('lots.show', $lot)
            ->with('status', "Lot {$lot->numero} modifie.");
    }

    /** Only validated sales count against a lot's produced quantity. */
    private function lignesValidees(): callable
    {
        return fn (Builder $query) => $query->whereHas(
            'vente',
            fn (Builder $vente) => $vente->where('statut', 'validee')
        );
    }

    private function modelesActifs(?int $inclure = null)
    {
        return Modele::where('actif', true)
            ->when($inclure, fn (Builder $q) => $q->orWhere('id', $inclure))
            ->orderBy('designation')
            ->get();
    }
}
