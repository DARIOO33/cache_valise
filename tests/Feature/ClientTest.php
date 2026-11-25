<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_un_client_peut_etre_cree(): void
    {
        $this->post(route('clients.store'), ['nom' => 'Boutique Centre', 'telephone' => '20 000 000'])
            ->assertRedirect();

        $this->assertDatabaseHas('clients', ['nom' => 'Boutique Centre']);
    }

    public function test_le_nom_est_obligatoire(): void
    {
        $this->post(route('clients.store'), ['nom' => ''])->assertSessionHasErrors('nom');
    }

    public function test_la_fiche_montre_le_solde_du(): void
    {
        $client = Client::create(['nom' => 'Boutique Centre']);

        Vente::create([
            'numero_facture' => 'FAC-2026-0001',
            'date_vente' => '2026-11-25',
            'client_id' => $client->id,
            'statut' => 'validee',
            'total' => 1800,
        ]);

        $client->ventes()->first()->reglements()->create([
            'date_reglement' => '2026-11-25',
            'montant' => 1000,
            'mode' => 'espece',
        ]);

        $this->get(route('clients.show', $client))
            ->assertStatus(200)
            ->assertSee('Solde du')
            ->assertSee('800,000 DT');
    }

    public function test_un_client_sans_vente_est_supprimable(): void
    {
        $client = Client::create(['nom' => 'A supprimer']);

        $this->delete(route('clients.destroy', $client))->assertRedirect(route('clients.index'));

        $this->assertDatabaseCount('clients', 0);
    }

    public function test_un_client_avec_ventes_nest_pas_supprimable(): void
    {
        $client = Client::create(['nom' => 'Boutique Centre']);

        Vente::create([
            'numero_facture' => 'FAC-2026-0001',
            'date_vente' => '2026-11-25',
            'client_id' => $client->id,
            'statut' => 'validee',
            'total' => 100,
        ]);

        $this->delete(route('clients.destroy', $client))->assertSessionHasErrors('suppression');
        $this->assertDatabaseCount('clients', 1);
    }
}
