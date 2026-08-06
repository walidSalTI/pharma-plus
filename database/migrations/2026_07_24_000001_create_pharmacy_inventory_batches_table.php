<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_inventory_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('pharmacy_inventory_id')
                ->constrained('pharmacy_inventories')
                ->cascadeOnDelete();

            $table->string('batch_number')->nullable();
            $table->integer('quantity');
            $table->decimal('wholesale_price', 10, 2);
            $table->date('expiration_date');

            $table->timestamps();

            $table->index('expiration_date');
            $table->index(['pharmacy_inventory_id', 'expiration_date'], 'batch_inv_exp_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_inventory_batches');
    }
};
