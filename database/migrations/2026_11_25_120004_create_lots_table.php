<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('modele_id')->constrained('modeles')->restrictOnDelete();
            $table->unsignedInteger('quantite_produite');
            $table->date('date_production');
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->enum('etat', ['en_preparation', 'termine', 'annule'])->default('en_preparation');
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index(['etat', 'date_production']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
