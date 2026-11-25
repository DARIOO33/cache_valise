<?php

namespace App\Http\Controllers;

use App\Http\Requests\LigneVenteRequest;
use App\Models\LigneVente;
use App\Models\Vente;
use App\Services\SalesService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class LigneVenteController extends Controller
{
    public function store(LigneVenteRequest $request, Vente $vente, SalesService $ventes): RedirectResponse
    {
        try {
            $ventes->ajouterLigne($vente, $request->validated());
        } catch (RuntimeException $e) {
            return redirect()->route('ventes.show', $vente)->withErrors(['ligne' => $e->getMessage()]);
        }

        return redirect()->route('ventes.show', $vente)->with('status', 'Ligne ajoutee.');
    }

    public function destroy(Vente $vente, LigneVente $ligne, SalesService $ventes): RedirectResponse
    {
        abort_unless($ligne->vente_id === $vente->id, 404);

        try {
            $ventes->supprimerLigne($vente, $ligne);
        } catch (RuntimeException $e) {
            return redirect()->route('ventes.show', $vente)->withErrors(['ligne' => $e->getMessage()]);
        }

        return redirect()->route('ventes.show', $vente)->with('status', 'Ligne supprimee.');
    }
}
