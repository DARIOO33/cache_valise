<?php

namespace App\Services;

use App\Models\Charge;
use App\Models\MouvementCaisse;
use App\Models\PaiementCharge;
use App\Models\Reglement;
use App\Models\Vente;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The single cash box (section 3.6).
 *
 * One rule governs this whole class: an invoice never touches the caisse,
 * only a payment does, and only when its mode is `espece`. Cheques and
 * transfers are recorded as payments and leave the cash balance untouched.
 *
 * The journal is append-only. Cancelling writes a contre-ecriture; nothing
 * is ever deleted or edited.
 */
class CashService
{
    /** Movement types whose direction is fixed by the type itself. */
    private const SENS = [
        'solde_initial' => 'entree',
        'alimentation' => 'entree',
        'reglement_client' => 'entree',
        'paiement_charge' => 'sortie',
        'retrait' => 'sortie',
        'depot_bancaire' => 'sortie',
    ];

    public function solde(): float
    {
        $entrees = (float) MouvementCaisse::where('sens', 'entree')->sum('montant');
        $sorties = (float) MouvementCaisse::where('sens', 'sortie')->sum('montant');

        return round($entrees - $sorties, 3);
    }

    public function resteDu(Vente $vente): float
    {
        return round((float) $vente->total - (float) $vente->reglements()->sum('montant'), 3);
    }

    public function resteAPayer(Charge $charge): float
    {
        return round((float) $charge->montant - (float) $charge->paiements()->sum('montant'), 3);
    }

    /**
     * Client payment. The caisse takes the amount paid, never the invoice total.
     */
    public function enregistrerReglement(Vente $vente, array $donnees): Reglement
    {
        if ($vente->statut !== 'validee') {
            throw new RuntimeException('Seule une facture validee peut recevoir un reglement.');
        }

        $montant = round((float) $donnees['montant'], 3);
        $reste = $this->resteDu($vente);

        if ($montant > $reste + 0.0005) {
            throw new RuntimeException(
                'Le reglement depasse le reste du ('.number_format($reste, 3, ',', ' ').' DT).'
            );
        }

        return DB::transaction(function () use ($vente, $donnees, $montant) {
            $reglement = $vente->reglements()->create([
                'date_reglement' => $donnees['date_reglement'],
                'montant' => $montant,
                'mode' => $donnees['mode'],
                'reference' => $donnees['reference'] ?? null,
                'notes' => $donnees['notes'] ?? null,
            ]);

            if ($reglement->mode === 'espece') {
                MouvementCaisse::create([
                    'date_mouvement' => $reglement->date_reglement,
                    'type' => 'reglement_client',
                    'sens' => 'entree',
                    'montant' => $reglement->montant,
                    'libelle' => "Reglement facture {$vente->numero_facture}",
                    'reglement_id' => $reglement->id,
                ]);
            }

            return $reglement;
        });
    }

    /** Supplier payment. Cash only leaves the box for an `espece` payment. */
    public function enregistrerPaiementCharge(Charge $charge, array $donnees): PaiementCharge
    {
        $montant = round((float) $donnees['montant'], 3);
        $reste = $this->resteAPayer($charge);

        if ($montant > $reste + 0.0005) {
            throw new RuntimeException(
                'Le paiement depasse le reste a payer ('.number_format($reste, 3, ',', ' ').' DT).'
            );
        }

        return DB::transaction(function () use ($charge, $donnees, $montant) {
            $paiement = $charge->paiements()->create([
                'date_paiement' => $donnees['date_paiement'],
                'montant' => $montant,
                'mode' => $donnees['mode'],
                'reference' => $donnees['reference'] ?? null,
                'notes' => $donnees['notes'] ?? null,
            ]);

            if ($paiement->mode === 'espece') {
                MouvementCaisse::create([
                    'date_mouvement' => $paiement->date_paiement,
                    'type' => 'paiement_charge',
                    'sens' => 'sortie',
                    'montant' => $paiement->montant,
                    'libelle' => "Paiement charge {$charge->numero_piece}",
                    'paiement_charge_id' => $paiement->id,
                ]);
            }

            return $paiement;
        });
    }

    /**
     * Movements with no payment behind them: opening balance, cash injection,
     * withdrawal, bank deposit, documented correction.
     */
    public function mouvementManuel(array $donnees): MouvementCaisse
    {
        $type = $donnees['type'];

        if (in_array($type, ['reglement_client', 'paiement_charge'], true)) {
            throw new RuntimeException('Ce type de mouvement est cree par un reglement, pas a la main.');
        }

        if ($type === 'solde_initial' && MouvementCaisse::where('type', 'solde_initial')->exists()) {
            throw new RuntimeException('Le solde initial a deja ete enregistre.');
        }

        return MouvementCaisse::create([
            'date_mouvement' => $donnees['date_mouvement'],
            'type' => $type,
            'sens' => self::SENS[$type] ?? $donnees['sens'],
            'montant' => round((float) $donnees['montant'], 3),
            'libelle' => $donnees['libelle'],
            'reference' => $donnees['reference'] ?? null,
        ]);
    }

    /**
     * Writes the mirror entry. The original stays in the journal for ever.
     *
     * The contre-ecriture deliberately carries no `reglement_id`: that column
     * is uniquely indexed, which is what stops a payment being posted twice.
     */
    public function annulerMouvement(MouvementCaisse $mouvement): MouvementCaisse
    {
        if ($mouvement->annule_mouvement_id !== null) {
            throw new RuntimeException('Une contre-ecriture ne s\'annule pas.');
        }

        if ($mouvement->contreEcriture()->exists()) {
            throw new RuntimeException('Ce mouvement a deja ete annule.');
        }

        return MouvementCaisse::create([
            'date_mouvement' => now()->toDateString(),
            'type' => 'correction',
            'sens' => $mouvement->sens === 'entree' ? 'sortie' : 'entree',
            'montant' => $mouvement->montant,
            'libelle' => "Contre-ecriture : {$mouvement->libelle}",
            'annule_mouvement_id' => $mouvement->id,
        ]);
    }

    /**
     * Reverses every cash movement behind an invoice's payments.
     * Called when a validated invoice is cancelled.
     */
    public function annulerMouvementsDe(Vente $vente): int
    {
        $vente->loadMissing('reglements.mouvementCaisse');
        $annules = 0;

        foreach ($vente->reglements as $reglement) {
            $mouvement = $reglement->mouvementCaisse;

            if ($mouvement && $mouvement->contreEcriture()->doesntExist()) {
                $this->annulerMouvement($mouvement);
                $annules++;
            }
        }

        return $annules;
    }
}
