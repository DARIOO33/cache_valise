<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModeleRequest;
use App\Models\Modele;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ModeleController extends Controller
{
    public function index(): View
    {
        $modeles = Modele::withCount('lots')
            ->orderBy('reference')
            ->paginate(15);

        return view('modeles.index', compact('modeles'));
    }

    public function create(): View
    {
        return view('modeles.create', ['modele' => new Modele(['actif' => true])]);
    }

    public function store(ModeleRequest $request): RedirectResponse
    {
        $modele = Modele::create($request->validated());

        return redirect()
            ->route('modeles.index')
            ->with('status', "Modele {$modele->reference} cree.");
    }

    public function edit(Modele $modele): View
    {
        return view('modeles.edit', compact('modele'));
    }

    public function update(ModeleRequest $request, Modele $modele): RedirectResponse
    {
        $modele->update($request->validated());

        return redirect()
            ->route('modeles.index')
            ->with('status', "Modele {$modele->reference} modifie.");
    }

    /**
     * Activation / desactivation (section 3.1). Models are never deleted:
     * lots reference them, and their history has to stay readable.
     */
    public function activation(Modele $modele): RedirectResponse
    {
        $modele->update(['actif' => ! $modele->actif]);

        return redirect()
            ->route('modeles.index')
            ->with('status', $modele->actif
                ? "Modele {$modele->reference} active."
                : "Modele {$modele->reference} desactive.");
    }
}
