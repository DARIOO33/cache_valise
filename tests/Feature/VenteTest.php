<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Client;
use App\Models\Lot;
use App\Models\Modele;
use App\Models\User;
use App\Models\Vente;
use App\Services\SalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenteTest extends TestCase
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

    /** Charges totalling 1 250 DT over 100 units: a unit cost of 12,500 DT. */
    private function chargerLeLot(): void
    {
        $lignes = [['tissu', 100, 8], ['couture', 1, 250], ['impression', 1, 150], ['autre', 1, 50]];

        foreach ($lignes as $i => [$categorie, $quantite, $prix]) {
            $this->ajouterCharge($categorie, $quantite, $prix, 'PC-TEST-'.$i);
        }
    }

    private function ajouterCharge(string $categorie, float $quantite, float $prix, string $piece): void
    {
        Charge::create([
            'numero_piece' => $piece,
            'date_charge' => '2026-11-20',
            'lot_id' => $this->lot->id,
            'categorie' => $categorie,
            'designation' => ucfirst($categorie),
            'quantite' => $quantite,
            'prix_unitaire' => $prix,
        ]);
    }

    private function brouillon(array $remplace = []): Vente
    {
        $this->post(route('ventes.store'), array_merge([
            'date_vente' => '2026-11-25',
        ], $remplace));

        return Vente::latest('id')->first();
    }

    private function ajouterLigne(Vente $vente, array $remplace = []): void
    {
        $this->post(route('ventes.lignes.store', $vente), array_merge([
            'lot_id' => $this->lot->id,
            'quantite' => 40,
            'prix_unitaire' => 45,
            'remise_pourcent' => 0,
        ], $remplace));
    }

    public function test_le_numero_de_facture_est_attribue_automatiquement(): void
    {
        $annee = now()->format('Y');

        $this->brouillon();
        $this->brouillon();

        $this->assertSame(
            ["FAC-{$annee}-0001", "FAC-{$annee}-0002"],
            Vente::orderBy('id')->pluck('numero_facture')->all()
        );
    }

    /** The whole worked example of section 14, through the HTTP routes. */
    public function test_la_validation_fige_le_cout_et_donne_une_marge_de_1300(): void
    {
        $this->chargerLeLot();

        $vente = $this->brouillon(['client_id' => Client::create(['nom' => 'Client test'])->id]);
        $this->ajouterLigne($vente);

        $this->post(route('ventes.validation', $vente))->assertRedirect(route('ventes.show', $vente));

        $vente->refresh()->load('lignes');
        $ligne = $vente->lignes->first();

        $this->assertSame('validee', $vente->statut);
        $this->assertNotNull($vente->validated_at);
        $this->assertSame(1800.0, (float) $vente->total);
        $this->assertSame(12.5, (float) $ligne->cout_unitaire);

        $ventes = app(SalesService::class);
        $this->assertSame(500.0, $ventes->coutDesProduitsVendus($vente));
        $this->assertSame(1300.0, $ventes->margeBrute($vente));

        $this->get(route('ventes.show', $vente))
            ->assertSee('1 800,000 DT')
            ->assertSee('500,000 DT')
            ->assertSee('1 300,000 DT');
    }

    public function test_une_correction_de_charge_ne_bouge_pas_la_marge_dune_facture_validee(): void
    {
        $this->chargerLeLot();

        $vente = $this->brouillon();
        $this->ajouterLigne($vente);
        $this->post(route('ventes.validation', $vente));

        // A 500 DT charge forgotten at the time: the lot now costs 1 750.
        $this->ajouterCharge('autre', 1, 500, 'PC-TEST-OUBLI');
        $this->assertSame(1750.0, (float) $this->lot->charges()->sum('montant'));

        $vente->refresh()->load('lignes');
        $ventes = app(SalesService::class);

        $this->assertSame(12.5, (float) $vente->lignes->first()->cout_unitaire);
        $this->assertSame(1300.0, $ventes->margeBrute($vente));
    }

    public function test_la_remise_est_un_pourcentage_de_la_ligne(): void
    {
        $vente = $this->brouillon();
        $this->ajouterLigne($vente, ['remise_pourcent' => 10]);

        $ligne = $vente->lignes()->first();

        $this->assertSame(180.0, (float) $ligne->remise_montant);
        $this->assertSame(1620.0, (float) $ligne->montant_ligne);

        $vente->refresh();
        $this->assertSame(1800.0, (float) $vente->sous_total);
        $this->assertSame(180.0, (float) $vente->total_remise);
        $this->assertSame(1620.0, (float) $vente->total);
    }

    public function test_une_facture_sans_ligne_ne_peut_pas_etre_validee(): void
    {
        $vente = $this->brouillon();

        $this->post(route('ventes.validation', $vente))->assertSessionHasErrors('validation');

        $this->assertSame('brouillon', $vente->fresh()->statut);
    }

    public function test_une_facture_validee_naccepte_plus_de_ligne(): void
    {
        $vente = $this->brouillon();
        $this->ajouterLigne($vente);
        $this->post(route('ventes.validation', $vente));

        $this->ajouterLigne($vente, ['quantite' => 5]);

        $this->assertSame(1, $vente->fresh()->lignes()->count());
    }

    public function test_une_facture_validee_ne_peut_plus_etre_modifiee(): void
    {
        $vente = $this->brouillon();
        $this->ajouterLigne($vente);
        $this->post(route('ventes.validation', $vente));

        $this->put(route('ventes.update', $vente), ['date_vente' => '2026-12-01'])
            ->assertSessionHasErrors('statut');

        $this->assertSame('2026-11-25', $vente->fresh()->date_vente->toDateString());
    }

    public function test_une_vente_comptoir_na_pas_de_client(): void
    {
        $vente = $this->brouillon(['nom_comptoir' => 'Passage']);

        $this->assertNull($vente->client_id);

        $this->get(route('ventes.show', $vente))
            ->assertStatus(200)
            ->assertSee('Vente comptoir');
    }

    public function test_une_ligne_peut_etre_retiree_dun_brouillon(): void
    {
        $vente = $this->brouillon();
        $this->ajouterLigne($vente);
        $ligne = $vente->lignes()->first();

        $this->delete(route('ventes.lignes.destroy', [$vente, $ligne]))->assertRedirect();

        $this->assertSame(0, $vente->fresh()->lignes()->count());
        $this->assertSame(0.0, (float) $vente->fresh()->total);
    }

    public function test_un_lot_annule_nest_pas_vendable(): void
    {
        $this->lot->update(['etat' => 'annule']);

        $vente = $this->brouillon();
        $this->ajouterLigne($vente);

        $this->assertSame(0, $vente->fresh()->lignes()->count());
    }

    public function test_un_lot_en_preparation_est_vendable_avec_avertissement(): void
    {
        $this->lot->update(['etat' => 'en_preparation']);

        $vente = $this->brouillon();
        $this->ajouterLigne($vente);

        $this->assertSame(1, $vente->fresh()->lignes()->count());

        $this->get(route('ventes.show', $vente))
            ->assertSee('Avertissement')
            ->assertSee('La vente reste autorisee');
    }

    public function test_une_facture_annulee_sort_du_chiffre_daffaires(): void
    {
        $gardee = $this->brouillon();
        $this->ajouterLigne($gardee);
        $this->post(route('ventes.validation', $gardee));

        $annulee = $this->brouillon();
        $this->ajouterLigne($annulee);
        $this->post(route('ventes.validation', $annulee));
        $this->post(route('ventes.annulation', $annulee));

        $this->assertSame('annulee', $annulee->fresh()->statut);
        $this->assertSame(1800.0, (float) Vente::where('statut', 'validee')->sum('total'));

        // The number is kept, so the series has no gap.
        $this->assertNotNull($annulee->fresh()->numero_facture);
    }

    public function test_une_vente_ne_peut_pas_etre_supprimee(): void
    {
        $vente = $this->brouillon();

        $this->delete(url("/ventes/{$vente->id}"))->assertStatus(405);
        $this->assertDatabaseCount('ventes', 1);
    }

    public function test_la_quantite_vendue_remonte_sur_le_lot(): void
    {
        $vente = $this->brouillon();
        $this->ajouterLigne($vente);

        // A draft must not look like stock sold.
        $this->assertSame(0, $this->lot->fresh()->quantite_vendue);

        $this->post(route('ventes.validation', $vente));

        $this->assertSame(40, $this->lot->fresh()->quantite_vendue);
        $this->assertSame(60, $this->lot->fresh()->quantite_restante);
    }
}
