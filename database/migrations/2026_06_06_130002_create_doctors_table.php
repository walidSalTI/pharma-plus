<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('specialization');
            $table->string('syndicate_card_image')->nullable();
            $table->string('doctor_secret_key')->nullable();
            $table->enum('verification_status', ['unverified', 'pending', 'approved', 'rejected'])->default('unverified');
            $table->timestamps();

            $table->index('specialization', 'idx_doctors_specialization');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
