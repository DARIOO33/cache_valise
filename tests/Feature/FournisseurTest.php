<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Fournisseur;
use App\Models\Lot;
use App\Models\Modele;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FournisseurTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function donnees(array $remplace = []): array
    {
        return array_merge([
            'nom' => 'Tissus SARL',
            'telephone' => '71 000 000',
            'type' => 'tissu',
        ], $remplace);
    }

    public function test_un_fournisseur_peut_etre_cree(): void
    {
        $this->post(route('fournisseurs.store'), $this->donnees())->assertRedirect();

        $this->assertDatabaseHas('fournisseurs', ['nom' => 'Tissus SARL', 'type' => 'tissu']);
    }

    public function test_le_nom_et_le_type_sont_obligatoires(): void
    {
        $this->post(route('fournisseurs.store'), ['nom' => '', 'type' => 'inconnu'])
            ->assertSessionHasErrors(['nom', 'type']);
    }

    public function test_un_email_invalide_est_refuse(): void
    {
        $this->post(route('fournisseurs.store'), $this->donnees(['email' => 'pas-un-email']))
            ->assertSessionHasErrors('email');
    }

    public function test_la_fiche_montre_lhistorique_des_charges(): void
    {
        $fournisseur = Fournisseur::create($this->donnees());
        $lot = $this->lot();

        Charge::create([
            'numero_piece' => 'PC-2026-0001',
            'date_charge' => '2026-11-20',
            'lot_id' => $lot->id,
            'fournisseur_id' => $fournisseur->id,
            'categorie' => 'tissu',
            'designation' => 'Tissu bleu',
            'quantite' => 100,
            'prix_unitaire' => 8,
        ]);

        $this->get(route('fournisseurs.show', $fournisseur))
            ->assertStatus(200)
            ->assertSee('Tissu bleu')
            ->assertSee('800,000 DT');
    }

    public function test_la_fiche_montre_lhistorique_des_paiements_et_la_dette(): void
    {
        $fournisseur = Fournisseur::create($this->donnees());

        $charge = Charge::create([
            'numero_piece' => 'PC-2026-0001',
            'date_charge' => '2026-11-20',
            'lot_id' => $this->lot()->id,
            'fournisseur_id' => $fournisseur->id,
            'categorie' => 'tissu',
            'designation' => 'Tissu bleu',
            'quantite' => 100,
            'prix_unitaire' => 8,
        ]);

        $this->post(route('charges.paiements.store', $charge), [
            'date_paiement' => '2026-11-22',
            'montant' => 300,
            'mode' => 'espece',
        ]);

        $this->get(route('fournisseurs.show', $fournisseur))
            ->assertStatus(200)
            ->assertSee('Historique des paiements')
            ->assertSee('Dette restante')
            ->assertSee('800,000 DT')   // engage
            ->assertSee('300,000 DT')   // paye
            ->assertSee('500,000 DT');  // dette restante
    }

    public function test_un_fournisseur_sans_charge_est_supprimable(): void
    {
        $fournisseur = Fournisseur::create($this->donnees());

        $this->delete(route('fournisseurs.destroy', $fournisseur))
            ->assertRedirect(route('fournisseurs.index'));

        $this->assertDatabaseCount('fournisseurs', 0);
    }

    public function test_un_fournisseur_avec_charges_nest_pas_supprimable(): void
    {
        $fournisseur = Fournisseur::create($this->donnees());

        Charge::create([
            'numero_piece' => 'PC-2026-0001',
            'date_charge' => '2026-11-20',
            'lot_id' => $this->lot()->id,
            'fournisseur_id' => $fournisseur->id,
            'categorie' => 'tissu',
            'designation' => 'Tissu',
            'quantite' => 1,
            'prix_unitaire' => 10,
        ]);

        $this->delete(route('fournisseurs.destroy', $fournisseur))
            ->assertSessionHasErrors('suppression');

        $this->assertDatabaseCount('fournisseurs', 1);
    }

    private function lot(): Lot
    {
        $modele = Modele::create([
            'reference' => 'CV002',
            'designation' => 'Cache-valise moyen',
            'prix_conseille' => 45,
        ]);

        return Lot::create([
            'numero' => 'LOT-2026-001',
            'modele_id' => $modele->id,
            'quantite_produite' => 100,
            'date_production' => '2026-11-25',
            'etat' => 'termine',
        ]);
    }
}
