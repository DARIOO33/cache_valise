<?php

namespace App\Http\Controllers;

use App\Http\Requests\FournisseurRequest;
use App\Models\Fournisseur;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FournisseurController extends Controller
{
    public function index(): View
    {
        $fournisseurs = Fournisseur::withCount('charges')
            ->withSum('charges as total_charges', 'montant')
            ->orderBy('nom')
            ->paginate(15);

        return view('fournisseurs.index', compact('fournisseurs'));
    }

    public function create(): View
    {
        return view('fournisseurs.create', ['fournisseur' => new Fournisseur(['type' => 'tissu'])]);
    }

    public function store(FournisseurRequest $request): RedirectResponse
    {
        $fournisseur = Fournisseur::create($request->validated());

        return redirect()
            ->route('fournisseurs.show', $fournisseur)
            ->with('status', "Fournisseur {$fournisseur->nom} cree.");
    }

    public function show(Fournisseur $fournisseur): View
    {
        $fournisseur->load([
            'charges' => fn ($q) => $q->with('lot')->orderByDesc('date_charge'),
        ]);

        return view('fournisseurs.show', compact('fournisseur'));
    }

    public function edit(Fournisseur $fournisseur): View
    {
        return view('fournisseurs.edit', compact('fournisseur'));
    }

    public function update(FournisseurRequest $request, Fournisseur $fournisseur): RedirectResponse
    {
        $fournisseur->update($request->validated());

        return redirect()
            ->route('fournisseurs.show', $fournisseur)
            ->with('status', "Fournisseur {$fournisseur->nom} modifie.");
    }

    /**
     * Deleting is allowed only while nothing references the supplier.
     * Once charges exist, the history has to stay readable.
     */
    public function destroy(Fournisseur $fournisseur): RedirectResponse
    {
        if ($fournisseur->charges()->exists()) {
            return redirect()
                ->route('fournisseurs.show', $fournisseur)
                ->withErrors(['suppression' => 'Ce fournisseur porte des charges : il ne peut pas etre supprime.']);
        }

        $nom = $fournisseur->nom;
        $fournisseur->delete();

        return redirect()
            ->route('fournisseurs.index')
            ->with('status', "Fournisseur {$nom} supprime.");
    }
}
