<?php

namespace Tests\Feature;

use App\Models\Lot;
use App\Models\Modele;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotTest extends TestCase
{
    use RefreshDatabase;

    private Modele $modele;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());

        $this->modele = Modele::create([
            'reference' => 'CV002',
            'designation' => 'Cache-valise moyen',
            'prix_conseille' => 45,
        ]);
    }

    private function donnees(array $remplace = []): array
    {
        return array_merge([
            'modele_id' => $this->modele->id,
            'quantite_produite' => 100,
            'date_production' => '2026-11-25',
            'etat' => 'en_preparation',
        ], $remplace);
    }

    public function test_le_numero_de_lot_est_attribue_automatiquement(): void
    {
        $annee = now()->format('Y');

        $this->post(route('lots.store'), $this->donnees())->assertRedirect();
        $this->post(route('lots.store'), $this->donnees())->assertRedirect();

        $this->assertSame(
            ["LOT-{$annee}-001", "LOT-{$annee}-002"],
            Lot::orderBy('id')->pluck('numero')->all()
        );
    }

    public function test_le_numero_nest_pas_pris_dans_la_requete(): void
    {
        $annee = now()->format('Y');

        $this->post(route('lots.store'), $this->donnees(['numero' => 'LOT-TRICHE']));

        $this->assertSame("LOT-{$annee}-001", Lot::first()->numero);
    }

    public function test_un_lot_peut_etre_cree(): void
    {
        $this->post(route('lots.store'), $this->donnees())->assertRedirect();

        $this->assertDatabaseHas('lots', [
            'modele_id' => $this->modele->id,
            'quantite_produite' => 100,
            'etat' => 'en_preparation',
        ]);
    }

    public function test_le_modele_doit_exister(): void
    {
        $this->post(route('lots.store'), $this->donnees(['modele_id' => 9999]))
            ->assertSessionHasErrors('modele_id');
    }

    public function test_la_quantite_doit_etre_au_moins_un(): void
    {
        $this->post(route('lots.store'), $this->donnees(['quantite_produite' => 0]))
            ->assertSessionHasErrors('quantite_produite');
    }

    public function test_la_date_de_fin_ne_precede_pas_le_debut(): void
    {
        $this->post(route('lots.store'), $this->donnees([
            'date_debut' => '2026-11-20',
            'date_fin' => '2026-11-10',
        ]))->assertSessionHasErrors('date_fin');
    }

    public function test_letat_est_contraint(): void
    {
        $this->post(route('lots.store'), $this->donnees(['etat' => 'livre']))
            ->assertSessionHasErrors('etat');
    }

    public function test_la_liste_affiche_la_quantite_restante(): void
    {
        $this->post(route('lots.store'), $this->donnees());

        $this->get(route('lots.index'))
            ->assertStatus(200)
            ->assertSee('Cache-valise moyen')
            ->assertSee('100');
    }

    public function test_la_fiche_dun_lot_est_accessible(): void
    {
        $this->post(route('lots.store'), $this->donnees());
        $lot = Lot::first();

        $this->get(route('lots.show', $lot))
            ->assertStatus(200)
            ->assertSee($lot->numero)
            ->assertSee('Vendue / restante')
            ->assertSee('Cout unitaire')
            // Nothing sold yet, so all 100 units remain.
            ->assertSee('0 / 100');
    }

    public function test_un_lot_peut_etre_modifie(): void
    {
        $this->post(route('lots.store'), $this->donnees());
        $lot = Lot::first();

        $this->put(route('lots.update', $lot), $this->donnees(['etat' => 'termine']))
            ->assertRedirect(route('lots.show', $lot));

        $this->assertSame('termine', $lot->fresh()->etat);
        // The number never changes on update.
        $this->assertSame($lot->numero, $lot->fresh()->numero);
    }

    public function test_un_lot_ne_peut_pas_etre_supprime(): void
    {
        $this->post(route('lots.store'), $this->donnees());
        $lot = Lot::first();

        $this->delete(url("/lots/{$lot->id}"))->assertStatus(405);
        $this->assertDatabaseCount('lots', 1);
    }

    public function test_seuls_les_modeles_actifs_sont_proposes(): void
    {
        Modele::create([
            'reference' => 'CV099',
            'designation' => 'Modele retire',
            'prix_conseille' => 10,
            'actif' => false,
        ]);

        $this->get(route('lots.create'))
            ->assertStatus(200)
            ->assertSee('Cache-valise moyen')
            ->assertDontSee('Modele retire');
    }
}
