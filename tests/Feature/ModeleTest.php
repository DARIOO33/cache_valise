<?php

namespace Tests\Feature;

use App\Models\Modele;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModeleTest extends TestCase
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
            'reference' => 'CV001',
            'designation' => 'Cache-valise petit',
            'dimensions' => '50x30',
            'prix_conseille' => '35.000',
            'actif' => '1',
        ], $remplace);
    }

    public function test_la_liste_est_accessible(): void
    {
        Modele::create($this->donnees());

        $this->get(route('modeles.index'))
            ->assertStatus(200)
            ->assertSee('CV001')
            ->assertSee('Cache-valise petit');
    }

    public function test_un_modele_peut_etre_cree(): void
    {
        $this->post(route('modeles.store'), $this->donnees())
            ->assertRedirect(route('modeles.index'));

        $this->assertDatabaseHas('modeles', [
            'reference' => 'CV001',
            'designation' => 'Cache-valise petit',
            'prix_conseille' => '35.000',
            'actif' => 1,
        ]);
    }

    public function test_la_reference_doit_etre_unique(): void
    {
        Modele::create($this->donnees());

        $this->post(route('modeles.store'), $this->donnees(['designation' => 'Doublon']))
            ->assertSessionHasErrors('reference');

        $this->assertSame(1, Modele::count());
    }

    public function test_les_champs_obligatoires_sont_valides(): void
    {
        $this->post(route('modeles.store'), ['reference' => '', 'designation' => '', 'prix_conseille' => 'abc'])
            ->assertSessionHasErrors(['reference', 'designation', 'prix_conseille']);
    }

    public function test_un_modele_peut_etre_modifie(): void
    {
        $modele = Modele::create($this->donnees());

        $this->put(route('modeles.update', $modele), $this->donnees(['designation' => 'Cache-valise revu']))
            ->assertRedirect(route('modeles.index'));

        $this->assertSame('Cache-valise revu', $modele->fresh()->designation);
    }

    public function test_sa_propre_reference_ne_bloque_pas_la_modification(): void
    {
        $modele = Modele::create($this->donnees());

        $this->put(route('modeles.update', $modele), $this->donnees(['dimensions' => '60x40']))
            ->assertSessionHasNoErrors();
    }

    public function test_activation_et_desactivation(): void
    {
        $modele = Modele::create($this->donnees());

        $this->patch(route('modeles.activation', $modele));
        $this->assertFalse($modele->fresh()->actif);

        $this->patch(route('modeles.activation', $modele));
        $this->assertTrue($modele->fresh()->actif);
    }

    public function test_une_case_decochee_desactive_le_modele(): void
    {
        $donnees = $this->donnees();
        unset($donnees['actif']);

        $this->post(route('modeles.store'), $donnees);

        $this->assertFalse(Modele::first()->actif);
    }

    public function test_un_invite_est_redirige(): void
    {
        auth()->logout();

        $this->get(route('modeles.index'))->assertRedirect(route('login'));
    }
}
