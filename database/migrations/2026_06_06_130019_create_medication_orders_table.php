<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('pharmacy_id')->constrained('pharmacies')->cascadeOnDelete();
            $table->enum('status', ['pending', 'confirmed', 'ready', 'completed', 'cancelled']);
            $table->text('pharmacist_note')->nullable();
            $table->decimal('total_price', 10, 2);
            $table->string('invoice_number')->unique();
            $table->timestamps();

            $table->index('status', 'idx_orders_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_orders');
    }
};
