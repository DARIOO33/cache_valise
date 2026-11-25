<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mouvements_caisse', function (Blueprint $table) {
            $table->id();
            $table->date('date_mouvement');
            $table->enum('type', [
                'solde_initial',
                'alimentation',
                'reglement_client',
                'paiement_charge',
                'retrait',
                'depot_bancaire',
                'correction',
            ]);
            $table->enum('sens', ['entree', 'sortie']);
            // Always positive: the direction is carried by `sens`.
            $table->decimal('montant', 12, 3);
            $table->string('libelle');
            $table->string('reference')->nullable();
            $table->foreignId('reglement_id')->nullable()->constrained('reglements')->nullOnDelete();
            $table->foreignId('paiement_charge_id')->nullable()->constrained('paiements_charges')->nullOnDelete();
            $table->foreignId('annule_mouvement_id')->nullable()->constrained('mouvements_caisse')->nullOnDelete();
            $table->timestamps();

            // One payment can produce at most one cash movement, ever.
            $table->unique('reglement_id');
            $table->unique('paiement_charge_id');

            $table->index(['date_mouvement', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements_caisse');
    }
};
