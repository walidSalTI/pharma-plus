<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_patients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('medication_id')->constrained('medications')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->enum('state', ['permanent', 'temporary']);
            $table->foreignUuid('chronic_id')->nullable()->constrained('chronic_records')->nullOnDelete();
            $table->string('dosage');
            $table->integer('available_pills')->nullable();
            $table->enum('frequency', ['daily', 'specific_days', 'as_needed']);
            $table->boolean('refill_risk');
            $table->text('instructions_before')->nullable();
            $table->text('instructions_after')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_patients');
    }
};
