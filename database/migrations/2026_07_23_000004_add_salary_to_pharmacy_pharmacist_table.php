<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_pharmacist', function (Blueprint $table) {
            $table->decimal('salary', 10, 2)->after('orders_view_own')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_pharmacist', function (Blueprint $table) {
            $table->dropColumn('salary');
        });
    }
};
