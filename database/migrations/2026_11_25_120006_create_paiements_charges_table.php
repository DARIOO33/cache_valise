<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charge_id')->constrained('charges')->cascadeOnDelete();
            $table->date('date_paiement');
            $table->decimal('montant', 12, 3);
            $table->enum('mode', ['espece', 'virement', 'cheque'])->default('espece');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['charge_id', 'date_paiement']);
            $table->index('mode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements_charges');
    }
};
