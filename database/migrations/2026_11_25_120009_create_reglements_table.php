<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reglements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vente_id')->constrained('ventes')->restrictOnDelete();
            $table->date('date_reglement');
            $table->decimal('montant', 12, 3);
            $table->enum('mode', ['espece', 'virement', 'cheque'])->default('espece');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vente_id', 'date_reglement']);
            $table->index('mode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglements');
    }
};
