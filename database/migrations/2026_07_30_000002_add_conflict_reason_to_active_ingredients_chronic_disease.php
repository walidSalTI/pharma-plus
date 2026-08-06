<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('active_ingredients_chronic_disease', function (Blueprint $table) {
            $table->text('conflict_reason')->nullable()->after('ai_explanation');
        });
    }

    public function down(): void
    {
        Schema::table('active_ingredients_chronic_disease', function (Blueprint $table) {
            $table->dropColumn('conflict_reason');
        });
    }
};
