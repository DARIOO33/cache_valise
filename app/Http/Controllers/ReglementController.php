<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReglementRequest;
use App\Models\Vente;
use App\Services\CashService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class ReglementController extends Controller
{
    public function store(ReglementRequest $request, Vente $vente, CashService $caisse): RedirectResponse
    {
        try {
            $reglement = $caisse->enregistrerReglement($vente, $request->validated());
        } catch (RuntimeException $e) {
            return redirect()->route('ventes.show', $vente)->withErrors(['reglement' => $e->getMessage()]);
        }

        $message = $reglement->mode === 'espece'
            ? 'Reglement enregistre et entree de caisse creee.'
            : 'Reglement enregistre. La caisse especes est inchangee.';

        return redirect()->route('ventes.show', $vente)->with('status', $message);
    }
}
