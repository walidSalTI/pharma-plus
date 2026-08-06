<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rep_id')->constrained('scientific_reps')->cascadeOnDelete();
            $table->foreignUuid('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->text('notes')->nullable();
            $table->enum('status', ['upcoming', 'completed', 'cancelled']);
            $table->boolean('is_reminded');
            $table->timestamps();

            $table->index('scheduled_at', 'idx_schedules_scheduled_at');
            $table->index('status', 'idx_schedules_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_schedules');
    }
};
