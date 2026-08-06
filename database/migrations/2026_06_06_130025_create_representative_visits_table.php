<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('representative_visits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignUuid('rep_id')->constrained('scientific_reps')->cascadeOnDelete();
            $table->foreignUuid('schedule_id')->constrained('weekly_schedules')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamp('scanned_at');
            $table->boolean('verification_status');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('scanned_at', 'idx_visits_scanned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('representative_visits');
    }
};
