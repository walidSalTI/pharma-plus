<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medication_orders', function (Blueprint $table) {
            $table->decimal('total_cost', 10, 2)->after('total_price')->default(0);
            $table->text('notes')->after('pharmacist_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('medication_orders', function (Blueprint $table) {
            $table->dropColumn(['total_cost', 'notes']);
        });
    }
};
