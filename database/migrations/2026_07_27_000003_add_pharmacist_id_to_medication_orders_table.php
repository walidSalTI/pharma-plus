<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medication_orders', function (Blueprint $table) {
            $table->foreignUuid('pharmacist_id')->nullable()->after('pharmacy_id')->constrained('pharmacists')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('medication_orders', function (Blueprint $table) {
            $table->dropForeign(['pharmacist_id']);
            $table->dropColumn('pharmacist_id');
        });
    }
};
