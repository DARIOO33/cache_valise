<?php

namespace Database\Seeders;

use App\Models\Charge;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Models\Lot;
use App\Models\Modele;
use App\Models\Vente;
use App\Services\CashService;
use App\Services\SalesService;
use App\Services\SequenceService;
use Illuminate\Database\Seeder;

/**
 * A coherent demonstration dataset, built through the services so that every
 * rule is exercised: cost freezing at validation, cash movements for `espece`
 * payments only, and the audit trail.
 *
 * Lot 1 reproduces the worked example of section 14 exactly:
 * 800 + 250 + 150 + 50 = 1 250 DT over 100 units = 12,500 DT per unit.
 */
class DemoSeeder extends Seeder
{
    public function run(SequenceService $sequences, SalesService $ventes, CashService $caisse): void
    {
        if (Modele::exists()) {
            $this->command->warn('Des donnees existent deja. Lancez : php artisan migrate:fresh --seed puis ce seeder.');

            return;
        }

        // ---- Reference data -------------------------------------------------
        $modeles = collect([
            ['CV001', 'Cache-valise petit', '50 x 30 cm', 35],
            ['CV002', 'Cache-valise moyen', '65 x 40 cm', 45],
            ['CV003', 'Cache-valise grand', '80 x 50 cm', 55],
        ])->mapWithKeys(fn ($m) => [$m[0] => Modele::create([
            'reference' => $m[0], 'designation' => $m[1], 'dimensions' => $m[2],
            'prix_conseille' => $m[3], 'actif' => true,
        ])]);

        $fournisseurs = collect([
            ['Tissus du Sud', 'tissu', '71 300 100'],
            ['Atelier Couture Amal', 'couture', '98 220 440'],
            ["Imprim'Plus", 'impression', '71 880 550'],
        ])->mapWithKeys(fn ($f) => [$f[1] => Fournisseur::create([
            'nom' => $f[0], 'type' => $f[1], 'telephone' => $f[2],
        ])]);

        $clients = collect([
            ['Boutique Centre', '20 111 222', 'Avenue Habib Bourguiba, Tunis'],
            ['Maison Bagages', '22 333 444', 'Rue de Marseille, Sousse'],
            ['Hotel Riviera', '24 555 666', 'Corniche, Hammamet'],
        ])->mapWithKeys(fn ($c) => [$c[0] => Client::create([
            'nom' => $c[0], 'telephone' => $c[1], 'adresse' => $c[2],
        ])]);

        // ---- Opening cash balance -------------------------------------------
        $caisse->mouvementManuel([
            'date_mouvement' => '2026-09-01',
            'type' => 'solde_initial',
            'montant' => 2000,
            'libelle' => 'Solde de depart de la caisse',
        ]);

        // ---- Lots and their charges -----------------------------------------
        $lots = [];

        $lots[1] = $this->lot($sequences, $modeles['CV002'], 100, '2026-09-10', 'termine');
        $this->charge($sequences, $lots[1], $fournisseurs['tissu'], 'tissu', 'Tissu bleu marine', 100, 'metre', 8, '2026-09-05');
        $this->charge($sequences, $lots[1], $fournisseurs['couture'], 'couture', 'Couture du lot', 1, 'forfait', 250, '2026-09-08');
        $this->charge($sequences, $lots[1], $fournisseurs['impression'], 'impression', 'Impression logo', 1, 'forfait', 150, '2026-09-09');
        $this->charge($sequences, $lots[1], null, 'autre', 'Transport atelier', 1, 'forfait', 50, '2026-09-10');

        $lots[2] = $this->lot($sequences, $modeles['CV001'], 150, '2026-10-05', 'termine');
        $this->charge($sequences, $lots[2], $fournisseurs['tissu'], 'tissu', 'Tissu gris', 120, 'metre', 6.5, '2026-10-01');
        $this->charge($sequences, $lots[2], $fournisseurs['couture'], 'couture', 'Couture du lot', 1, 'forfait', 300, '2026-10-03');
        $this->charge($sequences, $lots[2], $fournisseurs['impression'], 'impression', 'Impression logo', 1, 'forfait', 120, '2026-10-04');

        // Deliberately left `en_preparation`, to show the non-blocking warning.
        $lots[3] = $this->lot($sequences, $modeles['CV003'], 60, '2026-11-15', 'en_preparation');
        $this->charge($sequences, $lots[3], $fournisseurs['tissu'], 'tissu', 'Tissu renforce', 90, 'metre', 9, '2026-11-12');
        $this->charge($sequences, $lots[3], $fournisseurs['couture'], 'couture', 'Couture du lot', 1, 'forfait', 240, '2026-11-14');

        // ---- Invoices --------------------------------------------------------
        // Section 14: 40 units at 45 DT = 1 800 DT, cash payment of 1 000.
        $f1 = $this->facture($sequences, $ventes, '2026-09-20', $clients['Boutique Centre']);
        $ventes->ajouterLigne($f1, ['lot_id' => $lots[1]->id, 'quantite' => 40, 'prix_unitaire' => 45]);
        $ventes->valider($f1);
        $caisse->enregistrerReglement($f1->refresh(), [
            'date_reglement' => '2026-09-20', 'montant' => 1000, 'mode' => 'espece',
        ]);

        // A 10% line discount, settled in full by bank transfer: caisse untouched.
        $f2 = $this->facture($sequences, $ventes, '2026-10-12', $clients['Maison Bagages']);
        $ventes->ajouterLigne($f2, ['lot_id' => $lots[2]->id, 'quantite' => 50, 'prix_unitaire' => 35, 'remise_pourcent' => 10]);
        $ventes->valider($f2);
        $caisse->enregistrerReglement($f2->refresh(), [
            'date_reglement' => '2026-10-15', 'montant' => 1575, 'mode' => 'virement', 'reference' => 'VIR-2026-0042',
        ]);

        // Walk-in sale, paid in cash on the spot.
        $f3 = $this->facture($sequences, $ventes, '2026-10-28', null, 'Client de passage');
        $ventes->ajouterLigne($f3, ['lot_id' => $lots[1]->id, 'quantite' => 10, 'prix_unitaire' => 45]);
        $ventes->valider($f3);
        $caisse->enregistrerReglement($f3->refresh(), [
            'date_reglement' => '2026-10-28', 'montant' => 450, 'mode' => 'espece',
        ]);

        // Sold from a lot still in preparation, and not paid at all.
        $f4 = $this->facture($sequences, $ventes, '2026-11-18', $clients['Hotel Riviera']);
        $ventes->ajouterLigne($f4, ['lot_id' => $lots[3]->id, 'quantite' => 20, 'prix_unitaire' => 55]);
        $ventes->valider($f4);

        // Left as a draft on purpose: it must not count anywhere.
        $f5 = $this->facture($sequences, $ventes, '2026-11-24', $clients['Boutique Centre']);
        $ventes->ajouterLigne($f5, ['lot_id' => $lots[2]->id, 'quantite' => 5, 'prix_unitaire' => 35]);

        // ---- Supplier payments ------------------------------------------------
        $tissuLot1 = Charge::where('lot_id', $lots[1]->id)->where('categorie', 'tissu')->sole();
        $caisse->enregistrerPaiementCharge($tissuLot1, [
            'date_paiement' => '2026-09-25', 'montant' => 500, 'mode' => 'espece',
        ]);

        $coutureLot1 = Charge::where('lot_id', $lots[1]->id)->where('categorie', 'couture')->sole();
        $caisse->enregistrerPaiementCharge($coutureLot1, [
            'date_paiement' => '2026-09-26', 'montant' => 250, 'mode' => 'espece',
        ]);

        $tissuLot2 = Charge::where('lot_id', $lots[2]->id)->where('categorie', 'tissu')->sole();
        $caisse->enregistrerPaiementCharge($tissuLot2, [
            'date_paiement' => '2026-10-20', 'montant' => 780, 'mode' => 'virement', 'reference' => 'VIR-2026-0051',
        ]);

        $this->command->info('Jeu de donnees de demonstration cree.');
        $this->command->line('  3 modeles, 3 fournisseurs, 3 clients, 3 lots, 5 factures (4 validees, 1 brouillon)');
        $this->command->line('  Solde de caisse attendu : 2 700,000 DT');
    }

    private function lot(SequenceService $sequences, Modele $modele, int $quantite, string $date, string $etat): Lot
    {
        return Lot::create([
            'numero' => $sequences->suivant('lot', 2026),
            'modele_id' => $modele->id,
            'quantite_produite' => $quantite,
            'date_production' => $date,
            'etat' => $etat,
        ]);
    }

    private function charge(
        SequenceService $sequences, Lot $lot, ?Fournisseur $fournisseur, string $categorie,
        string $designation, float $quantite, string $unite, float $prix, string $date,
    ): Charge {
        return Charge::create([
            'numero_piece' => $sequences->suivant('piece', 2026),
            'date_charge' => $date,
            'lot_id' => $lot->id,
            'fournisseur_id' => $fournisseur?->id,
            'categorie' => $categorie,
            'designation' => $designation,
            'quantite' => $quantite,
            'unite' => $unite,
            'prix_unitaire' => $prix,
        ]);
    }

    private function facture(
        SequenceService $sequences, SalesService $ventes, string $date,
        ?Client $client, ?string $comptoir = null,
    ): Vente {
        return Vente::create([
            'numero_facture' => $sequences->suivant('facture', 2026),
            'date_vente' => $date,
            'client_id' => $client?->id,
            'nom_comptoir' => $comptoir,
            'statut' => 'brouillon',
        ]);
    }
}
