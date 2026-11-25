<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaiementChargeRequest;
use App\Models\Charge;
use App\Services\CashService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class PaiementChargeController extends Controller
{
    public function store(PaiementChargeRequest $request, Charge $charge, CashService $caisse): RedirectResponse
    {
        try {
            $paiement = $caisse->enregistrerPaiementCharge($charge, $request->validated());
        } catch (RuntimeException $e) {
            return redirect()->route('charges.show', $charge)->withErrors(['paiement' => $e->getMessage()]);
        }

        $message = $paiement->mode === 'espece'
            ? 'Paiement enregistre et sortie de caisse creee.'
            : 'Paiement enregistre. La caisse especes est inchangee.';

        return redirect()->route('charges.show', $charge)->with('status', $message);
    }
}
