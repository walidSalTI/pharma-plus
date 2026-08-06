<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chronic_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('chronic_disease_id')->constrained('chronic_diseases')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->integer('diagnosis_year');
            $table->enum('severity', ['low', 'medium', 'high'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chronic_records');
    }
};
