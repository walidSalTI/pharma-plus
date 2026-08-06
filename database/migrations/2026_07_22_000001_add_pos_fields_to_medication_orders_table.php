<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medication_orders', function (Blueprint $table) {
            $table->foreignUuid('patient_id')->nullable()->change();

            $table->string('source')->default('app')->after('status');

            $table->enum('type', ['sale', 'purchase', 'damaged', 'supplier_return', 'customer_return'])
                ->default('sale')
                ->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('medication_orders', function (Blueprint $table) {
            $table->foreignUuid('patient_id')->nullable(false)->change();

            $table->dropColumn(['source', 'type']);
        });
    }
};
