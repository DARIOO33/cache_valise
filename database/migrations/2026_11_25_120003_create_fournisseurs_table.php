<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('telephone')->nullable();
            $table->string('adresse')->nullable();
            $table->string('email')->nullable();
            $table->enum('type', ['tissu', 'couture', 'impression', 'autre'])->default('autre');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('nom');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fournisseurs');
    }
};
