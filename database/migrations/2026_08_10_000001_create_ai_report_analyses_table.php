<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_report_analyses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pharmacy_id')->constrained('pharmacies')->cascadeOnDelete();
            $table->string('type'); // financial, inventory, full, epidemic_demand
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('input_snapshot')->nullable(); // البيانات المالية التي تم تمريرها للـ AI
            $table->json('ai_insights')->nullable();    // النتيجة القادمة من Qwen
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->timestamps();

            $table->index(['pharmacy_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_report_analyses');
    }
};
