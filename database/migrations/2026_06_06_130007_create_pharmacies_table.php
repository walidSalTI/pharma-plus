<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('address');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('support_email');
            $table->string('support_number');
            $table->string('front_image');
            $table->foreignUuid('pharmacist_id')->nullable()->constrained('pharmacists')->nullOnDelete();
            $table->timestamps();

            $table->index('name', 'idx_pharmacies_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacies');
    }
};
