<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_inventories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pharmacy_id')->constrained('pharmacies')->cascadeOnDelete();
            $table->foreignUuid('medication_id')->constrained('medications')->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->integer('stock');
            $table->timestamp('last_updated');
            $table->integer('min_stock');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_inventories');
    }
};
