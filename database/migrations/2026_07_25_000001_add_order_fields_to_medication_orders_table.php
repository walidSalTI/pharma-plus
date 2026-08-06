<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medication_orders', function (Blueprint $table) {
            $table->boolean('is_returned')->default(false)->after('total_cost')->index();
            $table->string('supplier_name')->nullable()->after('total_cost');
            $table->enum('type', ['sale', 'purchase', 'damaged', 'supplier_return', 'customer_return', 'damage_reversal'])
                ->default('sale')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('medication_orders', function (Blueprint $table) {
            $table->dropColumn(['is_returned', 'supplier_name']);
            $table->enum('type', ['sale', 'purchase', 'damaged', 'supplier_return', 'customer_return'])
                ->default('sale')
                ->change();
        });
    }
};
