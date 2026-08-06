<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('active_ingredients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ingredient_name_en');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('ingredient_name_en', 'idx_active_ingredients_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('active_ingredients');
    }
};
