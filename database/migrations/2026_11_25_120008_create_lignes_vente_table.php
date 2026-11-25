<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lignes_vente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vente_id')->constrained('ventes')->cascadeOnDelete();
            $table->foreignId('lot_id')->constrained('lots')->restrictOnDelete();
            $table->string('designation');
            $table->unsignedInteger('quantite');
            $table->decimal('prix_unitaire', 12, 3);
            $table->decimal('remise_pourcent', 5, 2)->default(0);
            $table->decimal('remise_montant', 14, 3)->default(0);
            $table->decimal('montant_ligne', 14, 3)->default(0);
            // Frozen copy of the lot unit cost, written at validation. NULL while draft.
            $table->decimal('cout_unitaire', 12, 3)->nullable();
            $table->timestamps();

            $table->index(['vente_id', 'lot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_vente');
    }
};
