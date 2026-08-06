<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('medication_order_id')
                ->constrained('medication_orders')
                ->cascadeOnDelete();

            $table->foreignUuid('medication_id')
                ->constrained('medications');

            $table->integer('quantity');

            $table->decimal('price', 10, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_order_items');
    }
};
