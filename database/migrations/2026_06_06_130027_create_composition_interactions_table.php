<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('composition_interactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('composition_id')->constrained('active_ingredients')->cascadeOnDelete();
            $table->foreignUuid('interaction_composition_id')->constrained('active_ingredients')->cascadeOnDelete();
            $table->text('interaction_effect');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('composition_interactions');
    }
};
