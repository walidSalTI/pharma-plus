<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('medication_patient_id')->constrained('medication_patients')->cascadeOnDelete();
            $table->time('dose_time');
            $table->integer('day_of_week')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_schedules');
    }
};
