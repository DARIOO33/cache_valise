<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Lot;
use App\Models\Modele;
use App\Models\PaiementCharge;
use App\Models\User;
use App\Services\ProductionCostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChargeTest extends TestCase
{
    use RefreshDatabase;

    private Lot $lot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());

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

    private function donnees(array $remplace = []): array
    {
        return array_merge([
            'date_charge' => '2026-11-20',
            'lot_id' => $this->lot->id,
            'categorie' => 'tissu',
            'designation' => 'Tissu bleu',
            'quantite' => 100,
            'prix_unitaire' => 8,
        ], $remplace);
    }

    /** The worked example of section 14, driven entirely through the HTTP routes. */
    public function test_le_cout_unitaire_affiche_est_de_12_500(): void
    {
        $this->post(route('charges.store'), $this->donnees(['categorie' => 'tissu', 'designation' => 'Tissu', 'quantite' => 100, 'prix_unitaire' => 8]));
        $this->post(route('charges.store'), $this->donnees(['categorie' => 'couture', 'designation' => 'Couture', 'quantite' => 1, 'prix_unitaire' => 250]));
        $this->post(route('charges.store'), $this->donnees(['categorie' => 'impression', 'designation' => 'Impression', 'quantite' => 1, 'prix_unitaire' => 150]));
        $this->post(route('charges.store'), $this->donnees(['categorie' => 'autre', 'designation' => 'Transport', 'quantite' => 1, 'prix_unitaire' => 50]));

        $couts = app(ProductionCostService::class);
        $this->assertSame(1250.0, $couts->coutTotal($this->lot));
        $this->assertSame(12.5, $couts->coutUnitaire($this->lot));

        $this->get(route('lots.show', $this->lot))
            ->assertStatus(200)
            ->assertSee('1 250,000 DT')
            ->assertSee('12,500 DT');
    }

    public function test_le_numero_de_piece_est_attribue_automatiquement(): void
    {
        $annee = now()->format('Y');

        $this->post(route('charges.store'), $this->donnees());
        $this->post(route('charges.store'), $this->donnees());

        $this->assertSame(
            ["PC-{$annee}-0001", "PC-{$annee}-0002"],
            Charge::orderBy('id')->pluck('numero_piece')->all()
        );
    }

    public function test_le_montant_nest_jamais_pris_dans_la_requete(): void
    {
        $this->post(route('charges.store'), $this->donnees(['montant' => 99999]));

        $this->assertSame(800.0, (float) Charge::first()->montant);
    }

    public function test_un_forfait_a_une_quantite_de_un(): void
    {
        $this->post(route('charges.store'), $this->donnees([
            'designation' => 'Forfait couture',
            'quantite' => 1,
            'unite' => 'forfait',
            'prix_unitaire' => 250,
        ]));

        $this->assertSame(250.0, (float) Charge::first()->montant);
    }

    public function test_les_champs_obligatoires_sont_valides(): void
    {
        $this->post(route('charges.store'), [])
            ->assertSessionHasErrors(['date_charge', 'lot_id', 'categorie', 'designation', 'quantite', 'prix_unitaire']);
    }

    public function test_la_categorie_est_contrainte(): void
    {
        $this->post(route('charges.store'), $this->donnees(['categorie' => 'marketing']))
            ->assertSessionHasErrors('categorie');
    }

    public function test_la_repartition_par_categorie_est_calculee(): void
    {
        $this->post(route('charges.store'), $this->donnees(['categorie' => 'tissu', 'quantite' => 100, 'prix_unitaire' => 8]));
        $this->post(route('charges.store'), $this->donnees(['categorie' => 'couture', 'quantite' => 1, 'prix_unitaire' => 250]));

        $repartition = app(ProductionCostService::class)->repartitionParCategorie($this->lot);

        $this->assertSame(['tissu' => 800.0, 'couture' => 250.0], $repartition->all());
    }

    public function test_une_charge_peut_etre_modifiee(): void
    {
        $this->post(route('charges.store'), $this->donnees());
        $charge = Charge::first();

        $this->put(route('charges.update', $charge), $this->donnees(['prix_unitaire' => 9]))
            ->assertRedirect(route('lots.show', $this->lot->id));

        $this->assertSame(900.0, (float) $charge->fresh()->montant);
    }

    public function test_une_charge_sans_paiement_est_supprimable(): void
    {
        $this->post(route('charges.store'), $this->donnees());
        $charge = Charge::first();

        $this->delete(route('charges.destroy', $charge))
            ->assertRedirect(route('lots.show', $this->lot->id));

        $this->assertDatabaseCount('charges', 0);
    }

    public function test_une_charge_payee_nest_pas_supprimable(): void
    {
        $this->post(route('charges.store'), $this->donnees());
        $charge = Charge::first();

        PaiementCharge::create([
            'charge_id' => $charge->id,
            'date_paiement' => '2026-11-22',
            'montant' => 300,
            'mode' => 'espece',
        ]);

        $this->delete(route('charges.destroy', $charge))
            ->assertSessionHasErrors('suppression');

        $this->assertDatabaseCount('charges', 1);
    }

    public function test_la_liste_se_filtre_par_categorie(): void
    {
        $this->post(route('charges.store'), $this->donnees(['categorie' => 'tissu', 'designation' => 'Tissu bleu', 'quantite' => 100, 'prix_unitaire' => 8]));
        $this->post(route('charges.store'), $this->donnees(['categorie' => 'couture', 'designation' => 'Couture atelier', 'quantite' => 1, 'prix_unitaire' => 250]));

        $this->get(route('charges.index', ['categorie' => 'couture']))
            ->assertStatus(200)
            ->assertSee('Couture atelier')
            ->assertDontSee('Tissu bleu');
    }

    public function test_la_date_de_charge_est_distincte_du_paiement(): void
    {
        // Section 3.3: the expense date lives on the charge, never on the payment.
        $this->post(route('charges.store'), $this->donnees(['date_charge' => '2026-11-20']));

        $this->assertSame('2026-11-20', Charge::first()->date_charge->toDateString());
        $this->assertFalse(Schema::hasColumn('paiements_charges', 'date_charge'));
    }
}
