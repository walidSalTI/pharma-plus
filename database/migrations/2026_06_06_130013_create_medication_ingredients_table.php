<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_ingredients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('medication_id')->constrained('medications')->cascadeOnDelete();
            $table->foreignUuid('active_ingredient_id')->constrained('active_ingredients')->cascadeOnDelete();
            $table->string('active_ratio')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_ingredients');
    }
};
