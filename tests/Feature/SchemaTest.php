<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Models\LigneVente;
use App\Models\Lot;
use App\Models\Modele;
use App\Models\MouvementCaisse;
use App\Models\Reglement;
use App\Models\Vente;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checks the schema against the worked example in section 14 of the
 * cahier des charges: a lot of 100 units costing 1 250 DT, an invoice of
 * 40 units at 45 DT, and a partial cash payment of 1 000 DT.
 */
class SchemaTest extends TestCase
{
    use RefreshDatabase;

    private function lotDeCent(): Lot
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

    private function chargerLeLot(Lot $lot): void
    {
        $lignes = [
            ['PC-2026-0001', 'tissu', 'Tissu', 100, 8],
            ['PC-2026-0002', 'couture', 'Couture', 1, 250],
            ['PC-2026-0003', 'impression', 'Impression', 1, 150],
            ['PC-2026-0004', 'autre', 'Transport', 1, 50],
        ];

        foreach ($lignes as [$piece, $categorie, $designation, $quantite, $prix]) {
            Charge::create([
                'numero_piece' => $piece,
                'date_charge' => '2026-11-20',
                'lot_id' => $lot->id,
                'categorie' => $categorie,
                'designation' => $designation,
                'quantite' => $quantite,
                'prix_unitaire' => $prix,
            ]);
        }
    }

    public function test_la_base_calcule_le_montant_dune_charge(): void
    {
        $lot = $this->lotDeCent();

        $charge = Charge::create([
            'numero_piece' => 'PC-2026-0001',
            'date_charge' => '2026-11-20',
            'lot_id' => $lot->id,
            'categorie' => 'tissu',
            'designation' => 'Tissu',
            'quantite' => 100,
            'prix_unitaire' => 8,
        ]);

        // `montant` was never written by the application: MySQL generated it.
        $this->assertSame(800.0, (float) $charge->fresh()->montant);
    }

    public function test_le_cout_unitaire_du_lot_est_de_12_500(): void
    {
        $lot = $this->lotDeCent();
        $this->chargerLeLot($lot);

        $coutTotal = (float) $lot->charges()->sum('montant');
        $coutUnitaire = $coutTotal / $lot->quantite_produite;

        $this->assertSame(1250.0, $coutTotal);
        $this->assertSame(12.5, $coutUnitaire);
    }

    public function test_une_vente_fige_le_cout_unitaire_sur_sa_ligne(): void
    {
        $lot = $this->lotDeCent();
        $this->chargerLeLot($lot);

        $vente = Vente::create([
            'numero_facture' => 'FAC-2026-0001',
            'date_vente' => '2026-11-25',
            'client_id' => Client::create(['nom' => 'Client test'])->id,
            'statut' => 'validee',
            'validated_at' => now(),
            'sous_total' => 1800,
            'total_remise' => 0,
            'total' => 1800,
        ]);

        LigneVente::create([
            'vente_id' => $vente->id,
            'lot_id' => $lot->id,
            'designation' => 'Cache-valise moyen',
            'quantite' => 40,
            'prix_unitaire' => 45,
            'montant_ligne' => 1800,
            'cout_unitaire' => 12.5,
        ]);

        // A later correction to the lot's charges must not move the margin.
        Charge::create([
            'numero_piece' => 'PC-2026-0099',
            'date_charge' => '2026-11-26',
            'lot_id' => $lot->id,
            'categorie' => 'autre',
            'designation' => 'Charge oubliee',
            'quantite' => 1,
            'prix_unitaire' => 500,
        ]);

        $ligne = $vente->lignes()->first();
        $cogs = $ligne->quantite * (float) $ligne->cout_unitaire;

        $this->assertSame(12.5, (float) $ligne->cout_unitaire);
        $this->assertSame(500.0, $cogs);
        $this->assertSame(1300.0, (float) $vente->total - $cogs);

        // The lot's own cost did move, which is exactly why the line froze a copy.
        $this->assertSame(1750.0, (float) $lot->charges()->sum('montant'));
    }

    public function test_un_reglement_ne_peut_produire_quun_seul_mouvement_de_caisse(): void
    {
        $vente = Vente::create([
            'numero_facture' => 'FAC-2026-0002',
            'date_vente' => '2026-11-25',
            'statut' => 'validee',
            'total' => 1800,
        ]);

        $reglement = Reglement::create([
            'vente_id' => $vente->id,
            'date_reglement' => '2026-11-25',
            'montant' => 1000,
            'mode' => 'espece',
        ]);

        MouvementCaisse::create([
            'date_mouvement' => '2026-11-25',
            'type' => 'reglement_client',
            'sens' => 'entree',
            'montant' => 1000,
            'libelle' => 'Reglement FAC-2026-0002',
            'reglement_id' => $reglement->id,
        ]);

        // The caisse took 1 000, not the invoice's 1 800.
        $this->assertSame(1000.0, (float) MouvementCaisse::where('sens', 'entree')->sum('montant'));
        $this->assertSame(800.0, (float) $vente->total - (float) $vente->reglements()->sum('montant'));

        // Posting the same payment twice is refused by the database itself.
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

    public function test_une_vente_comptoir_na_pas_de_client(): void
    {
        $vente = Vente::create([
            'numero_facture' => 'FAC-2026-0003',
            'date_vente' => '2026-11-25',
            'nom_comptoir' => 'Passage',
            'statut' => 'validee',
            'total' => 90,
        ]);

        $this->assertNull($vente->client_id);
        $this->assertNull($vente->client);
    }

    public function test_un_lot_vendu_ne_peut_pas_etre_supprime(): void
    {
        $lot = $this->lotDeCent();

        $vente = Vente::create([
            'numero_facture' => 'FAC-2026-0004',
            'date_vente' => '2026-11-25',
            'statut' => 'brouillon',
        ]);

        LigneVente::create([
            'vente_id' => $vente->id,
            'lot_id' => $lot->id,
            'designation' => 'Cache-valise moyen',
            'quantite' => 1,
            'prix_unitaire' => 45,
            'montant_ligne' => 45,
        ]);

        $this->expectException(QueryException::class);
        $lot->delete();
    }

    public function test_les_relations_se_resolvent(): void
    {
        $lot = $this->lotDeCent();
        $fournisseur = Fournisseur::create(['nom' => 'Tissus SARL', 'type' => 'tissu']);

        Charge::create([
            'numero_piece' => 'PC-2026-0010',
            'date_charge' => '2026-11-20',
            'lot_id' => $lot->id,
            'fournisseur_id' => $fournisseur->id,
            'categorie' => 'tissu',
            'designation' => 'Tissu',
            'quantite' => 10,
            'prix_unitaire' => 8,
        ]);

        $charge = Charge::with(['lot.modele', 'fournisseur'])->first();

        $this->assertSame('LOT-2026-001', $charge->lot->numero);
        $this->assertSame('CV002', $charge->lot->modele->reference);
        $this->assertSame('Tissus SARL', $charge->fournisseur->nom);
        $this->assertCount(1, $fournisseur->charges);
        $this->assertCount(1, $lot->charges);
    }
}
