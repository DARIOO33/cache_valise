<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReglementRequest;
use App\Models\PaiementCharge;
use App\Models\Reglement;
use App\Models\Vente;
use App\Services\CashService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ReglementController extends Controller
{
    /**
     * The Reglements screen of section 6: client receipts and supplier
     * payments side by side. Entry stays on the invoice and the charge,
     * where the amount due is known; this screen is the consolidated view.
     */
    public function index(Request $request): View
    {
        $filtres = $request->only(['du', 'au', 'mode']);

        $encaissements = Reglement::with('vente.client')
            ->when($filtres['du'] ?? null, fn (Builder $q, $du) => $q->whereDate('date_reglement', '>=', $du))
            ->when($filtres['au'] ?? null, fn (Builder $q, $au) => $q->whereDate('date_reglement', '<=', $au))
            ->when($filtres['mode'] ?? null, fn (Builder $q, $m) => $q->where('mode', $m))
            ->orderByDesc('date_reglement')->orderByDesc('id')
            ->get();

        $paiements = PaiementCharge::with(['charge.fournisseur', 'charge.lot'])
            ->when($filtres['du'] ?? null, fn (Builder $q, $du) => $q->whereDate('date_paiement', '>=', $du))
            ->when($filtres['au'] ?? null, fn (Builder $q, $au) => $q->whereDate('date_paiement', '<=', $au))
            ->when($filtres['mode'] ?? null, fn (Builder $q, $m) => $q->where('mode', $m))
            ->orderByDesc('date_paiement')->orderByDesc('id')
            ->get();

        return view('reglements.index', [
            'encaissements' => $encaissements,
            'paiements' => $paiements,
            'totalEncaisse' => (float) $encaissements->sum('montant'),
            'totalPaye' => (float) $paiements->sum('montant'),
            'encaisseEspeces' => (float) $encaissements->where('mode', 'espece')->sum('montant'),
            'payeEspeces' => (float) $paiements->where('mode', 'espece')->sum('montant'),
            'filtres' => $filtres,
        ]);
    }

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
