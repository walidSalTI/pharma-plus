<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('search_telemetries', function (Blueprint $table) {
            $table->string('resolved_usage')->nullable()->after('resolved_active_ingredient_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('search_telemetries', function (Blueprint $table) {
            $table->dropIndex(['resolved_usage']);
            $table->dropColumn('resolved_usage');
        });
    }
};
