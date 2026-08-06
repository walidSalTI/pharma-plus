<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medication_order_items', function (Blueprint $table) {
            $table->foreignUuid('batch_id')
                ->nullable()
                ->after('medication_id')
                ->constrained('pharmacy_inventory_batches')
                ->nullOnDelete();

            $table->decimal('wholesale_price_at_sale', 10, 2)
                ->nullable()
                ->after('batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('medication_order_items', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
            $table->dropColumn(['batch_id', 'wholesale_price_at_sale']);
        });
    }
};
