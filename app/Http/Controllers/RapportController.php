<?php

namespace App\Http\Controllers;

use App\Models\MouvementCaisse;
use App\Services\CashService;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RapportController extends Controller
{
    public function index(Request $request, ReportService $rapports, CashService $caisse): View
    {
        return view('rapports.index', $this->donnees($request, $rapports, $caisse));
    }

    public function pdf(Request $request, ReportService $rapports, CashService $caisse): Response
    {
        $donnees = $this->donnees($request, $rapports, $caisse);

        return Pdf::loadView('rapports.pdf', $donnees)
            ->setPaper('a4')
            ->download('rapport-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * CSV rather than a .xlsx: section 5 allows either, and a semicolon-separated
     * UTF-8 file with a BOM opens straight into Excel in a French locale.
     */
    public function csv(Request $request, ReportService $rapports): StreamedResponse
    {
        [$du, $au] = $this->periode($request);
        $type = $request->string('rapport')->toString();

        [$nom, $entetes, $lignes] = match ($type) {
            'charges-lot' => [
                'charges-par-lot',
                ['Lot', 'Modele', 'Quantite produite', 'Cout total', 'Cout unitaire'],
                $rapports->chargesParLot($du, $au)->map(fn ($r) => [
                    $r->numero, $r->designation, $r->quantite_produite, $r->total, $r->cout_unitaire,
                ]),
            ],
            'ventes-modele' => [
                'ventes-par-modele',
                ['Reference', 'Designation', 'Quantite vendue', 'Chiffre affaires', 'Cout', 'Marge'],
                $rapports->ventesParModele($du, $au)->map(fn ($r) => [
                    $r->reference, $r->designation, $r->quantite, $r->chiffre, $r->cout, $r->marge,
                ]),
            ],
            'creances' => [
                'creances-clients',
                ['Client', 'Facture', 'Date', 'Total', 'Encaisse', 'Reste du'],
                $rapports->creancesClients()->map(fn ($r) => [
                    $r->nom, $r->numero_facture, $r->date_vente, $r->total, $r->encaisse, $r->reste,
                ]),
            ],
            'dettes' => [
                'dettes-fournisseurs',
                ['Fournisseur', 'Engage', 'Paye', 'Reste a payer'],
                $rapports->dettesFournisseurs()->map(fn ($r) => [$r->nom, $r->engage, $r->paye, $r->reste]),
            ],
            'caisse' => [
                'journal-caisse',
                ['Date', 'Type', 'Libelle', 'Sens', 'Montant'],
                MouvementCaisse::query()
                    ->when($du, fn ($q) => $q->whereDate('date_mouvement', '>=', $du))
                    ->when($au, fn ($q) => $q->whereDate('date_mouvement', '<=', $au))
                    ->orderBy('date_mouvement')->orderBy('id')->get()
                    ->map(fn (MouvementCaisse $m) => [
                        $m->date_mouvement->format('d/m/Y'), $m->type, $m->libelle, $m->sens, $m->montant,
                    ]),
            ],
            default => [
                'charges-par-categorie',
                ['Categorie', 'Nombre', 'Total'],
                $rapports->chargesParCategorie($du, $au)->map(fn ($r) => [$r->categorie, $r->nombre, $r->total]),
            ],
        };

        return response()->streamDownload(function () use ($entetes, $lignes) {
            $sortie = fopen('php://output', 'w');
            // BOM so Excel reads UTF-8 accents correctly.
            fwrite($sortie, "\xEF\xBB\xBF");
            fputcsv($sortie, $entetes, ';');

            foreach ($lignes as $ligne) {
                fputcsv($sortie, $ligne, ';');
            }

            fclose($sortie);
        }, $nom.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0: ?string, 1: ?string} */
    private function periode(Request $request): array
    {
        return [
            $request->date('du')?->toDateString(),
            $request->date('au')?->toDateString(),
        ];
    }

    private function donnees(Request $request, ReportService $rapports, CashService $caisse): array
    {
        [$du, $au] = $this->periode($request);

        return [
            'du' => $du,
            'au' => $au,
            'chiffreAffaires' => $rapports->chiffreAffaires($du, $au),
            'cogs' => $rapports->coutProduitsVendus($du, $au),
            'marge' => $rapports->margeBrute($du, $au),
            'totalCharges' => $rapports->totalCharges($du, $au),
            'chargesParCategorie' => $rapports->chargesParCategorie($du, $au),
            'chargesParLot' => $rapports->chargesParLot($du, $au),
            'ventesParModele' => $rapports->ventesParModele($du, $au),
            'creances' => $rapports->creancesClients(),
            'dettes' => $rapports->dettesFournisseurs(),
            'solde' => $caisse->solde(),
        ];
    }
}
