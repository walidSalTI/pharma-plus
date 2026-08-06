<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medications', function (Blueprint $table) {
            if (Schema::hasColumn('medications', 'is_verified')) {
                $table->dropColumn(['is_verified']);
            }
            if (Schema::hasColumn('medications', 'added_by_pharmacy_id')) {
                $table->dropForeign(['added_by_pharmacy_id']);
                $table->dropColumn(['added_by_pharmacy_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('medications', function (Blueprint $table) {
            $table->boolean('is_verified')->default(true)->after('image');
            $table->foreignUuid('added_by_pharmacy_id')->nullable()->constrained('pharmacies')->nullOnDelete()->after('usage_id');
        });
    }
};
