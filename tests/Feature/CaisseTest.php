<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Lot;
use App\Models\Modele;
use App\Models\MouvementCaisse;
use App\Models\User;
use App\Models\Vente;
use App\Services\CashService;
use App\Services\SalesService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The nine acceptance tests of section 14, and the rules behind them.
 */
class CaisseTest extends TestCase
{
    use RefreshDatabase;

    private Lot $lot;

    private CashService $caisse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->caisse = app(CashService::class);

        $modele = Modele::create([
            'reference' => 'CV002',
            'designation' => 'Cache-valise moyen',
            'prix_conseille' => 45,
        ]);

        $this->lot = Lot::create([
            'numero' => 'LOT-2026-001',
            'modele_id' => $modele->id,
            'quantite_produite' => 100,
            'date_production' => '2026-11-25',
            'etat' => 'termine',
        ]);
    }

    /** A validated invoice of 40 units at 45 DT: 1 800 DT. */
    private function factureValidee(): Vente
    {
        $this->post(route('ventes.store'), ['date_vente' => '2026-11-25']);
        $vente = Vente::latest('id')->first();

        $this->post(route('ventes.lignes.store', $vente), [
            'lot_id' => $this->lot->id,
            'quantite' => 40,
            'prix_unitaire' => 45,
        ]);

        $this->post(route('ventes.validation', $vente));

        return $vente->refresh();
    }

    private function charge(float $montant = 800): Charge
    {
        return Charge::create([
            'numero_piece' => 'PC-2026-'.fake()->unique()->numerify('####'),
            'date_charge' => '2026-11-20',
            'lot_id' => $this->lot->id,
            'categorie' => 'tissu',
            'designation' => 'Tissu',
            'quantite' => 1,
            'prix_unitaire' => $montant,
        ]);
    }

    public function test_un_reglement_especes_de_1000_entre_1000_en_caisse_pas_1800(): void
    {
        $vente = $this->factureValidee();
        $this->assertSame(1800.0, (float) $vente->total);

        $this->post(route('ventes.reglements.store', $vente), [
            'date_reglement' => '2026-11-25',
            'montant' => 1000,
            'mode' => 'espece',
        ])->assertRedirect(route('ventes.show', $vente));

        $this->assertSame(1000.0, $this->caisse->solde());
        $this->assertSame(800.0, $this->caisse->resteDu($vente->refresh()));

        $mouvement = MouvementCaisse::where('type', 'reglement_client')->sole();
        $this->assertSame('entree', $mouvement->sens);
        $this->assertSame(1000.0, (float) $mouvement->montant);
    }

    public function test_un_reglement_par_virement_ne_touche_pas_la_caisse(): void
    {
        $vente = $this->factureValidee();

        $this->post(route('ventes.reglements.store', $vente), [
            'date_reglement' => '2026-11-25',
            'montant' => 500,
            'mode' => 'virement',
            'reference' => 'VIR-123',
        ]);

        $this->assertSame(0.0, $this->caisse->solde());
        $this->assertSame(0, MouvementCaisse::count());
        // The payment itself was still recorded, so the client owes less.
        $this->assertSame(1300.0, $this->caisse->resteDu($vente->refresh()));
    }

    public function test_un_reglement_par_cheque_ne_touche_pas_la_caisse(): void
    {
        $vente = $this->factureValidee();

        $this->post(route('ventes.reglements.store', $vente), [
            'date_reglement' => '2026-11-25',
            'montant' => 200,
            'mode' => 'cheque',
            'reference' => 'CHQ-9',
        ]);

        $this->assertSame(0.0, $this->caisse->solde());
        $this->assertSame(1600.0, $this->caisse->resteDu($vente->refresh()));
    }

    public function test_un_paiement_fournisseur_en_especes_sort_300_de_la_caisse(): void
    {
        $charge = $this->charge(800);

        $this->caisse->mouvementManuel([
            'date_mouvement' => '2026-11-20',
            'type' => 'solde_initial',
            'montant' => 1000,
            'libelle' => 'Solde de depart',
        ]);

        $this->post(route('charges.paiements.store', $charge), [
            'date_paiement' => '2026-11-22',
            'montant' => 300,
            'mode' => 'espece',
        ])->assertRedirect(route('charges.show', $charge));

        $this->assertSame(700.0, $this->caisse->solde());

        $mouvement = MouvementCaisse::where('type', 'paiement_charge')->sole();
        $this->assertSame('sortie', $mouvement->sens);
        $this->assertSame(300.0, (float) $mouvement->montant);
        $this->assertSame(500.0, $this->caisse->resteAPayer($charge->refresh()));
    }

    public function test_un_paiement_fournisseur_par_virement_ne_touche_pas_la_caisse(): void
    {
        $charge = $this->charge(800);

        $this->post(route('charges.paiements.store', $charge), [
            'date_paiement' => '2026-11-22',
            'montant' => 300,
            'mode' => 'virement',
        ]);

        $this->assertSame(0.0, $this->caisse->solde());
        $this->assertSame(500.0, $this->caisse->resteAPayer($charge->refresh()));
    }

    public function test_un_meme_reglement_ne_peut_pas_produire_deux_mouvements(): void
    {
        $vente = $this->factureValidee();

        $this->post(route('ventes.reglements.store', $vente), [
            'date_reglement' => '2026-11-25',
            'montant' => 1000,
            'mode' => 'espece',
        ]);

        $reglement = $vente->reglements()->sole();

        $this->expectException(QueryException::class);

        MouvementCaisse::create([
            'date_mouvement' => '2026-11-25',
            'type' => 'reglement_client',
            'sens' => 'entree',
            'montant' => 1000,
            'libelle' => 'Doublon',
            'reglement_id' => $reglement->id,
        ]);
    }

    public function test_un_reglement_ne_peut_pas_depasser_le_reste_du(): void
    {
        $vente = $this->factureValidee();

        $this->post(route('ventes.reglements.store', $vente), [
            'date_reglement' => '2026-11-25', 'montant' => 1000, 'mode' => 'espece',
        ]);

        // A double submit of the same 1 000 would overshoot the 800 left.
        $this->post(route('ventes.reglements.store', $vente), [
            'date_reglement' => '2026-11-25', 'montant' => 1000, 'mode' => 'espece',
        ])->assertSessionHasErrors('reglement');

        $this->assertSame(1, $vente->refresh()->reglements()->count());
        $this->assertSame(1000.0, $this->caisse->solde());
    }

    public function test_un_paiement_ne_peut_pas_depasser_le_reste_a_payer(): void
    {
        $charge = $this->charge(800);

        $this->post(route('charges.paiements.store', $charge), [
            'date_paiement' => '2026-11-22', 'montant' => 900, 'mode' => 'espece',
        ])->assertSessionHasErrors('paiement');

        $this->assertSame(0, $charge->paiements()->count());
    }

    public function test_une_facture_en_brouillon_ne_recoit_pas_de_reglement(): void
    {
        $this->post(route('ventes.store'), ['date_vente' => '2026-11-25']);
        $vente = Vente::latest('id')->first();

        $this->post(route('ventes.reglements.store', $vente), [
            'date_reglement' => '2026-11-25', 'montant' => 100, 'mode' => 'espece',
        ])->assertSessionHasErrors('reglement');

        $this->assertSame(0, $vente->reglements()->count());
    }

    public function test_annuler_une_facture_ecrit_une_contre_ecriture(): void
    {
        $vente = $this->factureValidee();

        $this->post(route('ventes.reglements.store', $vente), [
            'date_reglement' => '2026-11-25', 'montant' => 1000, 'mode' => 'espece',
        ]);
        $this->assertSame(1000.0, $this->caisse->solde());

        $this->post(route('ventes.annulation', $vente))->assertRedirect();

        $this->assertSame('annulee', $vente->refresh()->statut);
        // Balance back to zero, and both entries still in the journal.
        $this->assertSame(0.0, $this->caisse->solde());
        $this->assertSame(2, MouvementCaisse::count());

        $contre = MouvementCaisse::whereNotNull('annule_mouvement_id')->sole();
        $this->assertSame('sortie', $contre->sens);
        $this->assertSame('correction', $contre->type);
        $this->assertNull($contre->reglement_id);
    }

    public function test_une_contre_ecriture_ne_sannule_pas(): void
    {
        $mouvement = $this->caisse->mouvementManuel([
            'date_mouvement' => '2026-11-20', 'type' => 'alimentation',
            'montant' => 500, 'libelle' => 'Apport',
        ]);

        $contre = $this->caisse->annulerMouvement($mouvement);

        $this->post(route('caisse.annulation', $contre))->assertSessionHasErrors('annulation');
        $this->post(route('caisse.annulation', $mouvement))->assertSessionHasErrors('annulation');

        $this->assertSame(2, MouvementCaisse::count());
        $this->assertSame(0.0, $this->caisse->solde());
    }

    public function test_le_solde_initial_ne_senregistre_quune_fois(): void
    {
        $this->post(route('caisse.store'), [
            'date_mouvement' => '2026-11-01', 'type' => 'solde_initial',
            'montant' => 1000, 'libelle' => 'Solde de depart',
        ])->assertRedirect(route('caisse.index'));

        $this->post(route('caisse.store'), [
            'date_mouvement' => '2026-11-02', 'type' => 'solde_initial',
            'montant' => 500, 'libelle' => 'Encore',
        ])->assertSessionHasErrors('mouvement');

        $this->assertSame(1, MouvementCaisse::where('type', 'solde_initial')->count());
    }

    public function test_les_mouvements_manuels_suivent_leur_sens(): void
    {
        foreach ([
            ['alimentation', 1000, 'entree'],
            ['retrait', 200, 'sortie'],
            ['depot_bancaire', 300, 'sortie'],
        ] as [$type, $montant, $sens]) {
            $this->post(route('caisse.store'), [
                'date_mouvement' => '2026-11-25', 'type' => $type,
                'montant' => $montant, 'libelle' => ucfirst($type),
            ]);

            $this->assertSame($sens, MouvementCaisse::where('type', $type)->sole()->sens);
        }

        $this->assertSame(500.0, $this->caisse->solde());
    }

    public function test_un_reglement_client_ne_se_saisit_pas_a_la_main(): void
    {
        $this->post(route('caisse.store'), [
            'date_mouvement' => '2026-11-25', 'type' => 'reglement_client',
            'montant' => 1000, 'libelle' => 'Triche',
        ])->assertSessionHasErrors('type');

        $this->assertSame(0, MouvementCaisse::count());
    }

    public function test_le_journal_affiche_le_solde_cumule(): void
    {
        $this->caisse->mouvementManuel([
            'date_mouvement' => '2026-11-01', 'type' => 'solde_initial',
            'montant' => 1000, 'libelle' => 'Solde de depart',
        ]);
        $this->caisse->mouvementManuel([
            'date_mouvement' => '2026-11-05', 'type' => 'retrait',
            'montant' => 250, 'libelle' => 'Retrait',
        ]);

        $this->get(route('caisse.index'))
            ->assertStatus(200)
            ->assertSee('Solde caisse especes')
            ->assertSee('750,000 DT');
    }

    public function test_le_cycle_complet_de_la_section_14(): void
    {
        // Lot of 100 charged 1 250 DT: unit cost 12,500 DT.
        foreach ([['tissu', 100, 8], ['couture', 1, 250], ['impression', 1, 150], ['autre', 1, 50]] as $i => [$cat, $q, $pu]) {
            Charge::create([
                'numero_piece' => "PC-2026-100{$i}",
                'date_charge' => '2026-11-20',
                'lot_id' => $this->lot->id,
                'categorie' => $cat,
                'designation' => ucfirst($cat),
                'quantite' => $q,
                'prix_unitaire' => $pu,
            ]);
        }

        $vente = $this->factureValidee();
        $ventes = app(SalesService::class);

        $this->assertSame(1800.0, (float) $vente->total);
        $this->assertSame(500.0, $ventes->coutDesProduitsVendus($vente));
        $this->assertSame(1300.0, $ventes->margeBrute($vente));

        // Cash payment of 1 000: caisse +1 000, client still owes 800.
        $this->post(route('ventes.reglements.store', $vente), [
            'date_reglement' => '2026-11-25', 'montant' => 1000, 'mode' => 'espece',
        ]);
        $this->assertSame(1000.0, $this->caisse->solde());
        $this->assertSame(800.0, $this->caisse->resteDu($vente->refresh()));

        // Supplier paid 300 in cash: caisse -300.
        $charge = Charge::where('categorie', 'couture')->sole();
        $this->post(route('charges.paiements.store', $charge), [
            'date_paiement' => '2026-11-26', 'montant' => 250, 'mode' => 'espece',
        ]);
        $this->assertSame(750.0, $this->caisse->solde());

        // A transfer leaves the cash box alone.
        $this->post(route('ventes.reglements.store', $vente), [
            'date_reglement' => '2026-11-27', 'montant' => 800, 'mode' => 'virement',
        ]);
        $this->assertSame(750.0, $this->caisse->solde());
        $this->assertSame(0.0, $this->caisse->resteDu($vente->refresh()));
    }
}
