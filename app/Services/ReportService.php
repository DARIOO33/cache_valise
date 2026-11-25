<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The states required by section 3.7. Everything is read-only and derived;
 * nothing here writes.
 *
 * COGS always uses `lignes_vente.cout_unitaire`, the cost frozen at
 * validation, never the lot's current cost.
 */
class ReportService
{
    public function chiffreAffaires(?string $du = null, ?string $au = null): float
    {
        return (float) $this->ventesValidees($du, $au)->sum('total');
    }

    public function coutProduitsVendus(?string $du = null, ?string $au = null): float
    {
        $total = DB::table('lignes_vente')
            ->join('ventes', 'ventes.id', '=', 'lignes_vente.vente_id')
            ->where('ventes.statut', 'validee')
            ->when($du, fn (Builder $q) => $q->whereDate('ventes.date_vente', '>=', $du))
            ->when($au, fn (Builder $q) => $q->whereDate('ventes.date_vente', '<=', $au))
            ->sum(DB::raw('lignes_vente.quantite * COALESCE(lignes_vente.cout_unitaire, 0)'));

        return round((float) $total, 3);
    }

    public function margeBrute(?string $du = null, ?string $au = null): float
    {
        return round($this->chiffreAffaires($du, $au) - $this->coutProduitsVendus($du, $au), 3);
    }

    public function chargesParCategorie(?string $du = null, ?string $au = null): Collection
    {
        return $this->charges($du, $au)
            ->select('categorie', DB::raw('SUM(montant) as total'), DB::raw('COUNT(*) as nombre'))
            ->groupBy('categorie')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => (object) ['categorie' => $r->categorie, 'total' => (float) $r->total, 'nombre' => (int) $r->nombre]);
    }

    public function chargesParLot(?string $du = null, ?string $au = null): Collection
    {
        return $this->charges($du, $au)
            ->join('lots', 'lots.id', '=', 'charges.lot_id')
            ->join('modeles', 'modeles.id', '=', 'lots.modele_id')
            ->select(
                'lots.numero',
                'lots.quantite_produite',
                'modeles.designation',
                DB::raw('SUM(charges.montant) as total')
            )
            ->groupBy('lots.id', 'lots.numero', 'lots.quantite_produite', 'modeles.designation')
            ->orderBy('lots.numero')
            ->get()
            ->map(fn ($r) => (object) [
                'numero' => $r->numero,
                'designation' => $r->designation,
                'quantite_produite' => (int) $r->quantite_produite,
                'total' => (float) $r->total,
                'cout_unitaire' => $r->quantite_produite > 0 ? round((float) $r->total / $r->quantite_produite, 3) : 0.0,
            ]);
    }

    public function ventesParModele(?string $du = null, ?string $au = null): Collection
    {
        return DB::table('lignes_vente')
            ->join('ventes', 'ventes.id', '=', 'lignes_vente.vente_id')
            ->join('lots', 'lots.id', '=', 'lignes_vente.lot_id')
            ->join('modeles', 'modeles.id', '=', 'lots.modele_id')
            ->where('ventes.statut', 'validee')
            ->when($du, fn (Builder $q) => $q->whereDate('ventes.date_vente', '>=', $du))
            ->when($au, fn (Builder $q) => $q->whereDate('ventes.date_vente', '<=', $au))
            ->select(
                'modeles.reference',
                'modeles.designation',
                DB::raw('SUM(lignes_vente.quantite) as quantite'),
                DB::raw('SUM(lignes_vente.montant_ligne) as chiffre'),
                DB::raw('SUM(lignes_vente.quantite * COALESCE(lignes_vente.cout_unitaire, 0)) as cout')
            )
            ->groupBy('modeles.id', 'modeles.reference', 'modeles.designation')
            ->orderByDesc('chiffre')
            ->get()
            ->map(fn ($r) => (object) [
                'reference' => $r->reference,
                'designation' => $r->designation,
                'quantite' => (int) $r->quantite,
                'chiffre' => (float) $r->chiffre,
                'cout' => (float) $r->cout,
                'marge' => round((float) $r->chiffre - (float) $r->cout, 3),
            ]);
    }

    /** Twelve months of revenue, oldest first, for the dashboard chart. */
    public function ventesParMois(int $mois = 12): Collection
    {
        $depuis = now()->startOfMonth()->subMonths($mois - 1);

        $lignes = DB::table('ventes')
            ->where('statut', 'validee')
            ->whereDate('date_vente', '>=', $depuis->toDateString())
            ->select(DB::raw("DATE_FORMAT(date_vente, '%Y-%m') as periode"), DB::raw('SUM(total) as total'))
            ->groupBy('periode')
            ->pluck('total', 'periode');

        return collect(range(0, $mois - 1))->map(function (int $i) use ($depuis, $lignes) {
            $mois = $depuis->copy()->addMonths($i);
            $cle = $mois->format('Y-m');

            return (object) [
                'periode' => $cle,
                'libelle' => $mois->translatedFormat('M Y'),
                'total' => (float) ($lignes[$cle] ?? 0),
            ];
        });
    }

    /** What each customer still owes on validated invoices. */
    public function creancesClients(): Collection
    {
        return DB::table('ventes')
            ->leftJoin('clients', 'clients.id', '=', 'ventes.client_id')
            ->leftJoin('reglements', 'reglements.vente_id', '=', 'ventes.id')
            ->where('ventes.statut', 'validee')
            ->select(
                DB::raw("COALESCE(clients.nom, CONCAT('Comptoir', COALESCE(CONCAT(' - ', ventes.nom_comptoir), ''))) as nom"),
                DB::raw('ventes.numero_facture'),
                DB::raw('ventes.date_vente'),
                DB::raw('ventes.total as total'),
                DB::raw('COALESCE(SUM(reglements.montant), 0) as encaisse')
            )
            ->groupBy('ventes.id', 'clients.nom', 'ventes.nom_comptoir', 'ventes.numero_facture', 'ventes.date_vente', 'ventes.total')
            ->orderBy('ventes.date_vente')
            ->get()
            ->map(fn ($r) => (object) [
                'nom' => $r->nom,
                'numero_facture' => $r->numero_facture,
                'date_vente' => $r->date_vente,
                'total' => (float) $r->total,
                'encaisse' => (float) $r->encaisse,
                'reste' => round((float) $r->total - (float) $r->encaisse, 3),
            ])
            ->filter(fn ($r) => $r->reste > 0)
            ->values();
    }

    /** What is still owed to each supplier. */
    public function dettesFournisseurs(): Collection
    {
        return DB::table('charges')
            ->leftJoin('fournisseurs', 'fournisseurs.id', '=', 'charges.fournisseur_id')
            ->leftJoin('paiements_charges', 'paiements_charges.charge_id', '=', 'charges.id')
            ->select(
                DB::raw("COALESCE(fournisseurs.nom, 'Sans fournisseur') as nom"),
                DB::raw('SUM(DISTINCT charges.montant) as engage'),
                DB::raw('COALESCE(SUM(paiements_charges.montant), 0) as paye')
            )
            ->groupBy('charges.id', 'fournisseurs.nom')
            ->get()
            ->groupBy('nom')
            ->map(function (Collection $lignes, string $nom) {
                $engage = (float) $lignes->sum('engage');
                $paye = (float) $lignes->sum('paye');

                return (object) [
                    'nom' => $nom,
                    'engage' => round($engage, 3),
                    'paye' => round($paye, 3),
                    'reste' => round($engage - $paye, 3),
                ];
            })
            ->sortByDesc('reste')
            ->values();
    }

    public function totalCreances(): float
    {
        return round((float) $this->creancesClients()->sum('reste'), 3);
    }

    public function totalDettes(): float
    {
        return round((float) $this->dettesFournisseurs()->sum('reste'), 3);
    }

    public function totalCharges(?string $du = null, ?string $au = null): float
    {
        return round((float) $this->charges($du, $au)->sum('montant'), 3);
    }

    private function ventesValidees(?string $du, ?string $au): Builder
    {
        return DB::table('ventes')
            ->where('statut', 'validee')
            ->when($du, fn (Builder $q) => $q->whereDate('date_vente', '>=', $du))
            ->when($au, fn (Builder $q) => $q->whereDate('date_vente', '<=', $au));
    }

    private function charges(?string $du, ?string $au): Builder
    {
        return DB::table('charges')
            ->when($du, fn (Builder $q) => $q->whereDate('date_charge', '>=', $du))
            ->when($au, fn (Builder $q) => $q->whereDate('date_charge', '<=', $au));
    }
}
