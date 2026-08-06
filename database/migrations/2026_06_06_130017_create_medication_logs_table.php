<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('schedule_id')->constrained('medication_schedules')->cascadeOnDelete();
            $table->timestamp('taken_at');
            $table->enum('status', ['taken', 'delayed', 'skipped']);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('taken_at', 'idx_medication_logs_taken_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_logs');
    }
};
