<?php

namespace App\Http\Controllers;

use App\Http\Requests\VenteRequest;
use App\Models\Client;
use App\Models\Lot;
use App\Models\Parametre;
use App\Models\Vente;
use App\Services\SalesService;
use App\Services\SequenceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;

class VenteController extends Controller
{
    public function index(Request $request): View
    {
        $statut = $request->string('statut')->toString();

        $ventes = Vente::with('client')
            ->withSum('reglements as encaisse', 'montant')
            ->when($statut, fn (Builder $q) => $q->where('statut', $statut))
            ->orderByDesc('date_vente')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('ventes.index', [
            'ventes' => $ventes,
            'statut' => $statut,
            'chiffreAffaires' => (float) Vente::where('statut', 'validee')->sum('total'),
        ]);
    }

    public function create(): View
    {
        return view('ventes.create', [
            'vente' => new Vente(['date_vente' => now()->toDateString()]),
            'clients' => Client::orderBy('nom')->get(),
        ]);
    }

    public function store(VenteRequest $request, SequenceService $sequences): RedirectResponse
    {
        $vente = Vente::create([
            ...$request->validated(),
            'numero_facture' => $sequences->suivant('facture'),
            'statut' => 'brouillon',
        ]);

        return redirect()
            ->route('ventes.show', $vente)
            ->with('status', "Facture {$vente->numero_facture} creee. Ajoutez ses lignes.");
    }

    public function show(Vente $vente, SalesService $ventes): View
    {
        $vente->load(['client', 'lignes.lot.modele', 'reglements.mouvementCaisse']);

        return view('ventes.show', [
            'vente' => $vente,
            'cogs' => $ventes->coutDesProduitsVendus($vente),
            'marge' => $ventes->margeBrute($vente),
            'encaisse' => (float) $vente->reglements->sum('montant'),
            'lots' => Lot::with('modele')
                ->where('etat', '!=', 'annule')
                ->orderByDesc('date_production')
                ->get(),
            // Non-blocking: section 3.2 forbids refusing a sale over stock.
            'lotsEnPreparation' => $vente->lignes
                ->filter(fn ($ligne) => $ligne->lot->etat === 'en_preparation')
                ->pluck('lot.numero')
                ->unique(),
        ]);
    }

    /** Section 3.4: print or export the invoice as a PDF. */
    public function pdf(Vente $vente): Response
    {
        $vente->load(['client', 'lignes.lot.modele', 'reglements']);

        return Pdf::loadView('ventes.pdf', [
            'vente' => $vente,
            'encaisse' => (float) $vente->reglements->sum('montant'),
            'parametres' => Parametre::pluck('valeur', 'cle'),
        ])->setPaper('a4')->download("facture-{$vente->numero_facture}.pdf");
    }

    public function edit(Vente $vente): View
    {
        return view('ventes.edit', [
            'vente' => $vente,
            'clients' => Client::orderBy('nom')->get(),
        ]);
    }

    public function update(VenteRequest $request, Vente $vente): RedirectResponse
    {
        if ($vente->statut !== 'brouillon') {
            return redirect()
                ->route('ventes.show', $vente)
                ->withErrors(['statut' => 'Seule une facture en brouillon peut etre modifiee.']);
        }

        $vente->update($request->validated());

        return redirect()
            ->route('ventes.show', $vente)
            ->with('status', "Facture {$vente->numero_facture} modifiee.");
    }

    public function validation(Vente $vente, SalesService $ventes): RedirectResponse
    {
        try {
            $ventes->valider($vente);
        } catch (RuntimeException $e) {
            return redirect()->route('ventes.show', $vente)->withErrors(['validation' => $e->getMessage()]);
        }

        return redirect()
            ->route('ventes.show', $vente)
            ->with('status', "Facture {$vente->numero_facture} validee. Le cout unitaire des lignes est fige.");
    }

    public function annulation(Vente $vente, SalesService $ventes): RedirectResponse
    {
        try {
            $ventes->annuler($vente);
        } catch (RuntimeException $e) {
            return redirect()->route('ventes.show', $vente)->withErrors(['annulation' => $e->getMessage()]);
        }

        return redirect()
            ->route('ventes.show', $vente)
            ->with('status', "Facture {$vente->numero_facture} annulee.");
    }
}
