<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('active_ingredients_chronic_disease', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('chronic_disease_id')->constrained('chronic_diseases')->cascadeOnDelete();
            $table->foreignUuid('active_ingredient_id')->constrained('active_ingredients')->cascadeOnDelete();
            $table->enum('risk_level', ['low', 'medium', 'high'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('active_ingredients_chronic_disease');
    }
};
