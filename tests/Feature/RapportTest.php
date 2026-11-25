<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Client;
use App\Models\Lot;
use App\Models\Modele;
use App\Models\User;
use App\Models\Vente;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RapportTest extends TestCase
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

        foreach ([['tissu', 100, 8], ['couture', 1, 250], ['impression', 1, 150], ['autre', 1, 50]] as $i => [$cat, $q, $pu]) {
            Charge::create([
                'numero_piece' => "PC-R-{$i}",
                'date_charge' => '2026-11-20',
                'lot_id' => $this->lot->id,
                'categorie' => $cat,
                'designation' => ucfirst($cat),
                'quantite' => $q,
                'prix_unitaire' => $pu,
            ]);
        }
    }

    private function factureValidee(string $date = '2026-11-25'): Vente
    {
        $this->post(route('ventes.store'), [
            'date_vente' => $date,
            'client_id' => Client::firstOrCreate(['nom' => 'Boutique Centre'])->id,
        ]);
        $vente = Vente::latest('id')->first();

        $this->post(route('ventes.lignes.store', $vente), [
            'lot_id' => $this->lot->id, 'quantite' => 40, 'prix_unitaire' => 45,
        ]);
        $this->post(route('ventes.validation', $vente));

        return $vente->refresh();
    }

    public function test_le_tableau_de_bord_affiche_les_indicateurs(): void
    {
        $this->factureValidee(now()->toDateString());

        $this->get(route('dashboard'))
            ->assertStatus(200)
            // The page prints a literal apostrophe; assertSee escapes by default.
            ->assertSee("Chiffre d'affaires du mois", false)
            ->assertSee('Solde caisse especes')
            ->assertSee('1 800,000 DT');
    }

    public function test_les_rapports_calculent_ca_cogs_et_marge(): void
    {
        $this->factureValidee();

        $rapports = app(ReportService::class);

        $this->assertSame(1800.0, $rapports->chiffreAffaires());
        $this->assertSame(500.0, $rapports->coutProduitsVendus());
        $this->assertSame(1300.0, $rapports->margeBrute());

        $this->get(route('rapports.index'))
            ->assertStatus(200)
            ->assertSee('1 800,000 DT')
            ->assertSee('500,000 DT')
            ->assertSee('1 300,000 DT');
    }

    public function test_le_filtre_de_periode_exclut_les_ventes_hors_plage(): void
    {
        $this->factureValidee('2026-11-25');

        $rapports = app(ReportService::class);

        $this->assertSame(1800.0, $rapports->chiffreAffaires('2026-11-01', '2026-11-30'));
        $this->assertSame(0.0, $rapports->chiffreAffaires('2026-12-01', '2026-12-31'));
    }

    public function test_les_charges_sont_regroupees_par_categorie(): void
    {
        $categories = app(ReportService::class)->chargesParCategorie()->pluck('total', 'categorie');

        $this->assertSame(800.0, $categories['tissu']);
        $this->assertSame(250.0, $categories['couture']);
        $this->assertSame(1250.0, (float) $categories->sum());
    }

    public function test_le_cout_de_revient_par_lot_est_de_12_500(): void
    {
        $lot = app(ReportService::class)->chargesParLot()->sole();

        $this->assertSame('LOT-2026-001', $lot->numero);
        $this->assertSame(1250.0, $lot->total);
        $this->assertSame(12.5, $lot->cout_unitaire);
    }

    public function test_les_ventes_par_modele_portent_la_marge(): void
    {
        $this->factureValidee();

        $ligne = app(ReportService::class)->ventesParModele()->sole();

        $this->assertSame('CV002', $ligne->reference);
        $this->assertSame(40, $ligne->quantite);
        $this->assertSame(1800.0, $ligne->chiffre);
        $this->assertSame(500.0, $ligne->cout);
        $this->assertSame(1300.0, $ligne->marge);
    }

    public function test_les_ventes_sont_regroupees_par_client(): void
    {
        $this->factureValidee();

        $ligne = app(ReportService::class)->ventesParClient()->sole();

        $this->assertSame('Boutique Centre', $ligne->nom);
        $this->assertSame(1, $ligne->factures);
        $this->assertSame(1800.0, $ligne->chiffre);
        $this->assertSame(1300.0, $ligne->marge);

        $this->get(route('rapports.index'))
            ->assertStatus(200)
            ->assertSee('Ventes par client')
            ->assertSee('Boutique Centre');
    }

    public function test_les_ventes_comptoir_sont_regroupees_a_part(): void
    {
        $this->post(route('ventes.store'), ['date_vente' => '2026-11-25', 'nom_comptoir' => 'Passage']);
        $vente = Vente::latest('id')->first();
        $this->post(route('ventes.lignes.store', $vente), [
            'lot_id' => $this->lot->id, 'quantite' => 1, 'prix_unitaire' => 45,
        ]);
        $this->post(route('ventes.validation', $vente));

        $this->assertSame(
            'Ventes comptoir',
            app(ReportService::class)->ventesParClient()->sole()->nom
        );
    }

    public function test_lecran_reglements_liste_les_deux_flux(): void
    {
        $vente = $this->factureValidee();

        $this->post(route('ventes.reglements.store', $vente), [
            'date_reglement' => '2026-11-25', 'montant' => 1000, 'mode' => 'espece',
        ]);

        $charge = Charge::where('categorie', 'couture')->sole();
        $this->post(route('charges.paiements.store', $charge), [
            'date_paiement' => '2026-11-26', 'montant' => 250, 'mode' => 'virement',
        ]);

        $this->get(route('reglements.index'))
            ->assertStatus(200)
            ->assertSee('Encaissements clients')
            ->assertSee('Paiements de charges')
            ->assertSee($vente->numero_facture)
            ->assertSee($charge->numero_piece)
            ->assertSee('entree de caisse')
            ->assertSee('hors caisse especes');
    }

    public function test_les_creances_et_dettes_sont_calculees(): void
    {
        $vente = $this->factureValidee();

        $this->post(route('ventes.reglements.store', $vente), [
            'date_reglement' => '2026-11-25', 'montant' => 1000, 'mode' => 'espece',
        ]);

        $rapports = app(ReportService::class);

        $this->assertSame(800.0, $rapports->totalCreances());
        // Nothing paid to suppliers yet: the whole 1 250 is still owed.
        $this->assertSame(1250.0, $rapports->totalDettes());
    }

    public function test_le_rapport_sexporte_en_pdf(): void
    {
        $this->factureValidee();

        $reponse = $this->get(route('rapports.pdf'));

        $reponse->assertStatus(200)->assertHeader('content-type', 'application/pdf');
        // Pdf::download() returns a plain response, not a streamed one.
        $this->assertStringStartsWith('%PDF', $reponse->getContent());
    }

    public function test_la_facture_sexporte_en_pdf(): void
    {
        $vente = $this->factureValidee();

        $reponse = $this->get(route('ventes.pdf', $vente));

        $reponse->assertStatus(200)->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'facture-'.$vente->numero_facture,
            $reponse->headers->get('content-disposition')
        );
    }

    public function test_les_rapports_sexportent_en_csv(): void
    {
        $this->factureValidee();

        $reponse = $this->get(route('rapports.csv', ['rapport' => 'ventes-modele']));
        $reponse->assertStatus(200);

        $csv = $reponse->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv, 'Le BOM UTF-8 manque : Excel casserait les accents.');
        $this->assertStringContainsString('Reference;Designation', $csv);
        $this->assertStringContainsString('CV002', $csv);
        $this->assertStringContainsString('1800', $csv);
    }

    public function test_le_csv_des_creances_liste_les_factures_impayees(): void
    {
        $vente = $this->factureValidee();
        $this->post(route('ventes.reglements.store', $vente), [
            'date_reglement' => '2026-11-25', 'montant' => 1000, 'mode' => 'espece',
        ]);

        $csv = $this->get(route('rapports.csv', ['rapport' => 'creances']))->streamedContent();

        $this->assertStringContainsString('Boutique Centre', $csv);
        $this->assertStringContainsString($vente->numero_facture, $csv);
        $this->assertStringContainsString('800', $csv);
    }

    public function test_une_facture_annulee_sort_des_rapports(): void
    {
        $vente = $this->factureValidee();
        $this->post(route('ventes.annulation', $vente));

        $rapports = app(ReportService::class);

        $this->assertSame(0.0, $rapports->chiffreAffaires());
        $this->assertSame(0.0, $rapports->coutProduitsVendus());
        $this->assertTrue($rapports->creancesClients()->isEmpty());
    }
}
