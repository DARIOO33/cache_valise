<?php

namespace App\Services;

use App\Models\LigneVente;
use App\Models\Lot;
use App\Models\Vente;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Invoice lifecycle (section 3.4).
 *
 * The rule that matters: at validation each line copies the lot's unit cost
 * into `cout_unitaire`. Charges corrected afterwards move the lot's cost but
 * must never move the margin of an invoice already issued.
 */
class SalesService
{
    public function __construct(
        private readonly ProductionCostService $couts,
        private readonly CashService $caisse,
    ) {}

    public function ajouterLigne(Vente $vente, array $donnees): LigneVente
    {
        $this->exigerBrouillon($vente);

        $lot = Lot::with('modele')->findOrFail($donnees['lot_id']);

        return DB::transaction(function () use ($vente, $lot, $donnees) {
            $ligne = $vente->lignes()->create([
                'lot_id' => $lot->id,
                'designation' => $donnees['designation'] ?? $lot->modele->designation,
                ...$this->calculerLigne(
                    (int) $donnees['quantite'],
                    (float) $donnees['prix_unitaire'],
                    (float) ($donnees['remise_pourcent'] ?? 0),
                ),
            ]);

            $this->recalculerTotaux($vente);

            return $ligne;
        });
    }

    public function supprimerLigne(Vente $vente, LigneVente $ligne): void
    {
        $this->exigerBrouillon($vente);

        DB::transaction(function () use ($vente, $ligne) {
            $ligne->delete();
            $this->recalculerTotaux($vente);
        });
    }

    /**
     * Freezes the cost of every line, then closes the invoice.
     */
    public function valider(Vente $vente): Vente
    {
        $this->exigerBrouillon($vente);

        if ($vente->lignes()->doesntExist()) {
            throw new RuntimeException('Une facture sans ligne ne peut pas etre validee.');
        }

        return DB::transaction(function () use ($vente) {
            $vente->load('lignes.lot');

            foreach ($vente->lignes as $ligne) {
                $ligne->update(['cout_unitaire' => $this->couts->coutUnitaire($ligne->lot)]);
            }

            $this->recalculerTotaux($vente);

            $vente->update([
                'statut' => 'validee',
                'validated_at' => now(),
            ]);

            return $vente->refresh();
        });
    }

    /**
     * Whole invoice only: a cancelled invoice stays visible, leaves CA, COGS
     * and quantite vendue, and keeps its number so the series has no gap.
     *
     * Every cash reglement behind it gets a contre-ecriture, so the caisse
     * balance returns to what it was and the journal keeps both entries.
     */
    public function annuler(Vente $vente): Vente
    {
        if ($vente->statut === 'annulee') {
            throw new RuntimeException('Cette facture est deja annulee.');
        }

        return DB::transaction(function () use ($vente) {
            $this->caisse->annulerMouvementsDe($vente);

            $vente->update(['statut' => 'annulee']);

            return $vente->refresh();
        });
    }

    /** COGS: quantity times the cost frozen on the line, never the lot's current cost. */
    public function coutDesProduitsVendus(Vente $vente): float
    {
        $vente->loadMissing('lignes');

        return round(
            $vente->lignes->sum(fn (LigneVente $l) => $l->quantite * (float) $l->cout_unitaire),
            3
        );
    }

    public function margeBrute(Vente $vente): float
    {
        return round((float) $vente->total - $this->coutDesProduitsVendus($vente), 3);
    }

    public function recalculerTotaux(Vente $vente): void
    {
        $lignes = $vente->lignes()->get();

        $sousTotal = $lignes->sum(fn (LigneVente $l) => (float) $l->quantite * (float) $l->prix_unitaire);
        $remise = $lignes->sum(fn (LigneVente $l) => (float) $l->remise_montant);

        $vente->update([
            'sous_total' => round($sousTotal, 3),
            'total_remise' => round($remise, 3),
            'total' => round($sousTotal - $remise, 3),
        ]);
    }

    /** @return array{quantite:int, prix_unitaire:float, remise_pourcent:float, remise_montant:float, montant_ligne:float} */
    private function calculerLigne(int $quantite, float $prixUnitaire, float $remisePourcent): array
    {
        $brut = $quantite * $prixUnitaire;
        $remise = round($brut * $remisePourcent / 100, 3);

        return [
            'quantite' => $quantite,
            'prix_unitaire' => $prixUnitaire,
            'remise_pourcent' => $remisePourcent,
            'remise_montant' => $remise,
            'montant_ligne' => round($brut - $remise, 3),
        ];
    }

    private function exigerBrouillon(Vente $vente): void
    {
        if ($vente->statut !== 'brouillon') {
            throw new RuntimeException('Seule une facture en brouillon peut etre modifiee.');
        }
    }
}
