<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('search_telemetries', function (Blueprint $table) {
            $table->string('resolved_product_name')->nullable()->after('searched_query');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('
                ALTER TABLE search_telemetries
                    MODIFY latitude DECIMAL(10, 8) NOT NULL,
                    MODIFY longitude DECIMAL(11, 8) NOT NULL
            ');

            Schema::table('search_telemetries', function (Blueprint $table) {
                $table->foreign('resolved_active_ingredient_id')
                    ->references('id')
                    ->on('active_ingredients');
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('search_telemetries', function (Blueprint $table) {
                $table->dropForeign('search_telemetries_resolved_active_ingredient_id_foreign');
            });

            DB::statement('
                ALTER TABLE search_telemetries
                    MODIFY latitude DECIMAL(10, 8) NULL,
                    MODIFY longitude DECIMAL(11, 8) NULL
            ');
        }

        Schema::table('search_telemetries', function (Blueprint $table) {
            $table->dropColumn('resolved_product_name');
        });
    }
};
