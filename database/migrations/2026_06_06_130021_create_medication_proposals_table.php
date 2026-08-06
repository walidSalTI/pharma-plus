<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_proposals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pharmacist_id')->constrained('pharmacists')->cascadeOnDelete();
            $table->foreignUuid('specialist_id')->nullable()->constrained('specialists')->cascadeOnDelete();
            $table->string('medication_name');
            $table->string('form');
            $table->string('image_url')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected']);
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('status', 'idx_proposals_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_proposals');
    }
};
