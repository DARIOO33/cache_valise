<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Charge;
use App\Models\Lot;
use App\Models\Modele;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Section 4: "les operations sensibles doivent etre historisees". */
class JournalTest extends TestCase
{
    use RefreshDatabase;

    private Lot $lot;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);

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

    private function factureValidee(): Vente
    {
        $this->post(route('ventes.store'), ['date_vente' => '2026-11-25']);
        $vente = Vente::latest('id')->first();

        $this->post(route('ventes.lignes.store', $vente), [
            'lot_id' => $this->lot->id, 'quantite' => 40, 'prix_unitaire' => 45,
        ]);
        $this->post(route('ventes.validation', $vente));

        return $vente->refresh();
    }

    public function test_une_charge_creee_est_historisee(): void
    {
        $this->post(route('charges.store'), [
            'date_charge' => '2026-11-20',
            'lot_id' => $this->lot->id,
            'categorie' => 'tissu',
            'designation' => 'Tissu',
            'quantite' => 100,
            'prix_unitaire' => 8,
        ]);

        $entree = AuditLog::where('auditable_type', Charge::class)->where('action', 'creation')->sole();

        $this->assertSame($this->admin->id, $entree->user_id);
        $this->assertSame('tissu', $entree->nouvelles_valeurs['categorie']);
        $this->assertNotNull($entree->ip);
        $this->assertNotNull($entree->created_at);
    }

    public function test_la_validation_dune_facture_est_historisee(): void
    {
        $vente = $this->factureValidee();

        $entree = AuditLog::where('action', 'validation_facture')->sole();

        $this->assertSame(Vente::class, $entree->auditable_type);
        $this->assertSame($vente->id, $entree->auditable_id);
        $this->assertSame($vente->numero_facture, $entree->nouvelles_valeurs['numero_facture']);
    }

    public function test_lannulation_dune_facture_est_historisee(): void
    {
        $vente = $this->factureValidee();

        $this->post(route('ventes.reglements.store', $vente), [
            'date_reglement' => '2026-11-25', 'montant' => 1000, 'mode' => 'espece',
        ]);
        $this->post(route('ventes.annulation', $vente));

        $annulation = AuditLog::where('action', 'annulation_facture')->sole();
        $this->assertSame('validee', $annulation->anciennes_valeurs['statut']);
        $this->assertSame(1, $annulation->nouvelles_valeurs['contre_ecritures']);

        $this->assertSame(1, AuditLog::where('action', 'contre_ecriture')->count());
    }

    public function test_le_recalcul_des_totaux_ne_pollue_pas_le_journal(): void
    {
        $this->post(route('ventes.store'), ['date_vente' => '2026-11-25']);
        $vente = Vente::latest('id')->first();

        $this->post(route('ventes.lignes.store', $vente), [
            'lot_id' => $this->lot->id, 'quantite' => 40, 'prix_unitaire' => 45,
        ]);

        // Adding a line recomputes sous_total/total_remise/total on the invoice.
        // Those are application-computed, so they must not log a change.
        $this->assertSame(
            0,
            AuditLog::where('auditable_type', Vente::class)->where('action', 'modification')->count()
        );
    }

    public function test_le_journal_est_consultable_et_filtrable(): void
    {
        $this->factureValidee();

        $this->get(route('journal.index'))
            ->assertStatus(200)
            ->assertSee('Journal des operations')
            ->assertSee('Validation de facture');

        $this->get(route('journal.index', ['action' => 'validation_facture']))
            ->assertStatus(200)
            ->assertSee('Validation de facture');
    }

    public function test_le_journal_na_aucune_route_decriture(): void
    {
        $this->post(route('journal.index'))->assertStatus(405);
        $this->delete(route('journal.index'))->assertStatus(405);
    }
}
