<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Charge;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Models\Lot;
use App\Models\Modele;
use App\Models\MouvementCaisse;
use App\Models\Vente;
use App\Services\CashService;
use App\Services\ProductionCostService;
use App\Services\ReportService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pins every figure the demonstration dataset is supposed to produce.
 * If a rule in the application changes, this test says so immediately.
 */
class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_le_jeu_de_donnees_est_complet(): void
    {
        $this->assertSame(3, Modele::count());
        $this->assertSame(3, Fournisseur::count());
        $this->assertSame(3, Client::count());
        $this->assertSame(3, Lot::count());
        $this->assertSame(9, Charge::count());
        $this->assertSame(5, Vente::count());
        $this->assertSame(4, Vente::where('statut', 'validee')->count());
        $this->assertSame(1, Vente::where('statut', 'brouillon')->count());
    }

    public function test_les_couts_unitaires_des_trois_lots(): void
    {
        $couts = app(ProductionCostService::class);

        $attendus = [
            'LOT-2026-001' => [1250.0, 12.5],
            'LOT-2026-002' => [1200.0, 8.0],
            'LOT-2026-003' => [1050.0, 17.5],
        ];

        foreach ($attendus as $numero => [$total, $unitaire]) {
            $lot = Lot::where('numero', $numero)->sole();

            $this->assertSame($total, $couts->coutTotal($lot), "cout total de {$numero}");
            $this->assertSame($unitaire, $couts->coutUnitaire($lot), "cout unitaire de {$numero}");
        }
    }

    public function test_le_chiffre_daffaires_et_la_marge(): void
    {
        $rapports = app(ReportService::class);

        // 1 800 + 1 575 + 450 + 1 100, le brouillon exclu.
        $this->assertSame(4925.0, $rapports->chiffreAffaires());
        // 40x12,5 + 50x8 + 10x12,5 + 20x17,5
        $this->assertSame(1375.0, $rapports->coutProduitsVendus());
        $this->assertSame(3550.0, $rapports->margeBrute());
    }

    public function test_la_remise_de_dix_pour_cent(): void
    {
        $vente = Vente::where('numero_facture', 'FAC-2026-0002')->sole();

        $this->assertSame(1750.0, (float) $vente->sous_total);
        $this->assertSame(175.0, (float) $vente->total_remise);
        $this->assertSame(1575.0, (float) $vente->total);
    }

    public function test_le_solde_de_caisse(): void
    {
        // 2 000 initial + 1 000 especes + 450 especes - 500 - 250.
        // Les 1 575 par virement et les 780 par virement n'y figurent pas.
        $this->assertSame(2700.0, app(CashService::class)->solde());
    }

    public function test_les_creances_et_les_dettes(): void
    {
        $rapports = app(ReportService::class);

        // FAC-0001 : 1 800 - 1 000 = 800 ; FAC-0004 : 1 100 impayes.
        $this->assertSame(1900.0, $rapports->totalCreances());
        // 3 500 de charges - 1 530 payes.
        $this->assertSame(1970.0, $rapports->totalDettes());
    }

    public function test_les_quantites_restantes_par_lot(): void
    {
        $attendus = [
            'LOT-2026-001' => [100, 50, 50],
            // Le brouillon de 5 unites ne compte pas.
            'LOT-2026-002' => [150, 50, 100],
            'LOT-2026-003' => [60, 20, 40],
        ];

        foreach ($attendus as $numero => [$produite, $vendue, $restante]) {
            $lot = Lot::where('numero', $numero)->sole();

            $this->assertSame($produite, $lot->quantite_produite, "produite {$numero}");
            $this->assertSame($vendue, $lot->quantite_vendue, "vendue {$numero}");
            $this->assertSame($restante, $lot->quantite_restante, "restante {$numero}");
        }
    }

    public function test_les_couts_sont_figes_sur_les_lignes(): void
    {
        $validees = Vente::where('statut', 'validee')->with('lignes')->get();

        foreach ($validees->flatMap->lignes as $ligne) {
            $this->assertNotNull($ligne->cout_unitaire, 'une ligne validee sans cout fige');
        }

        $brouillon = Vente::where('statut', 'brouillon')->with('lignes')->sole();
        $this->assertNull($brouillon->lignes->first()->cout_unitaire);
    }

    public function test_seuls_les_paiements_especes_ont_un_mouvement(): void
    {
        $caisse = app(CashService::class);

        $this->assertSame(1, MouvementCaisse::where('type', 'solde_initial')->count());
        $this->assertSame(2, MouvementCaisse::where('type', 'reglement_client')->count());
        $this->assertSame(2, MouvementCaisse::where('type', 'paiement_charge')->count());

        // Le virement client et le virement fournisseur n'ont cree aucun mouvement.
        $this->assertSame(5, MouvementCaisse::count());
        $this->assertSame(2700.0, $caisse->solde());
    }

    public function test_le_journal_daudit_a_tout_enregistre(): void
    {
        $this->assertGreaterThan(0, AuditLog::where('action', 'validation_facture')->count());
        $this->assertSame(4, AuditLog::where('action', 'validation_facture')->count());
    }

    public function test_le_seeder_ne_duplique_pas_les_donnees(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertSame(3, Modele::count());
        $this->assertSame(5, Vente::count());
    }
}
