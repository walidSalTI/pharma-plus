<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmaceutical_companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('commercial_name');
            $table->string('commercial_registration')->unique();
            $table->string('address');
            $table->string('phone');
            $table->string('license_number')->unique();
            $table->string('license_image');
            $table->enum('status', ['pending', 'active', 'suspended', 'rejected']);
            $table->timestamps();

            $table->index('commercial_name', 'idx_companies_commercial_name');
            $table->index('status', 'idx_companies_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmaceutical_companies');
    }
};
