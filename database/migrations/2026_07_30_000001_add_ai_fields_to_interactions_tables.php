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
        // ═══════════════════════════════════════════════════════════════
        // active_ingredients_chronic_disease
        // ═══════════════════════════════════════════════════════════════
        // 1. risk_level: ENUM('low','medium','high') → TINYINT (0/1/2)
        // NOTE: conflict_reason is added by 2026_07_30_000002.
        Schema::table('active_ingredients_chronic_disease', function (Blueprint $table) {
            $table->tinyInteger('risk_level_new')->nullable()->after('active_ingredient_id');
            $table->boolean('is_ai_verified')->default(false)->after('risk_level_new');
            $table->text('ai_explanation')->nullable()->after('is_ai_verified');
        });

        DB::statement("
            UPDATE active_ingredients_chronic_disease
            SET risk_level_new = CASE risk_level
                WHEN 'low'   THEN 0
                WHEN 'medium' THEN 1
                WHEN 'high'  THEN 2
            END
        ");

        Schema::table('active_ingredients_chronic_disease', function (Blueprint $table) {
            $table->dropColumn('risk_level');
        });

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE active_ingredients_chronic_disease CHANGE risk_level_new risk_level TINYINT NULL");
        } else {
            Schema::table('active_ingredients_chronic_disease', function (Blueprint $table) {
                $table->renameColumn('risk_level_new', 'risk_level');
            });
        }

        // ═══════════════════════════════════════════════════════════════
        // composition_interactions
        // ═══════════════════════════════════════════════════════════════
        Schema::table('composition_interactions', function (Blueprint $table) {
            $table->tinyInteger('risk_level')->nullable()->after('interaction_composition_id');
            $table->boolean('is_ai_verified')->default(false)->after('risk_level');
            $table->text('ai_explanation')->nullable()->after('is_ai_verified');
        });
    }

    public function down(): void
    {
        // Reverse composition_interactions
        Schema::table('composition_interactions', function (Blueprint $table) {
            $table->dropColumn('ai_explanation');
            $table->dropColumn('is_ai_verified');
            $table->dropColumn('risk_level');
        });

        // Reverse active_ingredients_chronic_disease
        Schema::table('active_ingredients_chronic_disease', function (Blueprint $table) {
            $table->dropColumn('conflict_reason');
            $table->dropColumn('ai_explanation');
            $table->dropColumn('is_ai_verified');
            $table->dropColumn('risk_level');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE active_ingredients_chronic_disease ADD risk_level ENUM('low','medium','high') NULL AFTER active_ingredient_id");
        } else {
            Schema::table('active_ingredients_chronic_disease', function (Blueprint $table) {
                $table->string('risk_level')->nullable();
            });
        }
    }
};
