<?php

namespace App\Http\Controllers;

use App\Http\Requests\LotRequest;
use App\Models\Lot;
use App\Models\Modele;
use App\Services\ProductionCostService;
use App\Services\SequenceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LotController extends Controller
{
    public function index(): View
    {
        $lots = Lot::with('modele')
            ->withQuantiteVendue()
            ->withSum('charges as cout_total', 'montant')
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

    public function show(Lot $lot, ProductionCostService $couts): View
    {
        $lot->load(['modele', 'charges' => fn ($q) => $q->with('fournisseur')->orderBy('date_charge')]);

        return view('lots.show', [
            'lot' => $lot,
            'coutTotal' => $couts->coutTotal($lot),
            'coutUnitaire' => $couts->coutUnitaire($lot),
            'repartition' => $couts->repartitionParCategorie($lot),
        ]);
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

    private function modelesActifs(?int $inclure = null): Collection
    {
        return Modele::where('actif', true)
            ->when($inclure, fn (Builder $q) => $q->orWhere('id', $inclure))
            ->orderBy('designation')
            ->get();
    }
}
