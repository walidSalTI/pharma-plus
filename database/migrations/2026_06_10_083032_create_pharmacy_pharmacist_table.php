<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_pharmacist', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('pharmacy_id')->constrained('pharmacies')->cascadeOnDelete();
            $table->foreignUuid('pharmacist_id')->constrained('pharmacists')->cascadeOnDelete();
            $table->boolean('pharmacy_manage')->default(false);
            $table->boolean('inventory_manage')->default(false);
            $table->boolean('operating_hours_manage')->default(false);
            $table->boolean('orders_process')->default(false);
            $table->boolean('orders_view_own')->default(false);
            $table->timestamps();

            $table->unique(['pharmacy_id', 'pharmacist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_pharmacist');
    }
};
