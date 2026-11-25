<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventes', function (Blueprint $table) {
            $table->id();
            $table->string('numero_facture')->unique();
            $table->date('date_vente');
            // NULL = vente comptoir, no named customer.
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('nom_comptoir')->nullable();
            $table->enum('statut', ['brouillon', 'validee', 'annulee'])->default('brouillon');
            $table->timestamp('validated_at')->nullable();
            $table->decimal('sous_total', 14, 3)->default(0);
            $table->decimal('total_remise', 14, 3)->default(0);
            $table->decimal('total', 14, 3)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['statut', 'date_vente']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventes');
    }
};
