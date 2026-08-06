<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'apps'])->default('cash')->after('category');
            $table->text('notes')->nullable()->after('expense_date');
            $table->string('attachment_path')->nullable()->after('notes');
            $table->index(['pharmacy_id', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['pharmacy_id', 'expense_date']);
            $table->dropColumn(['payment_method', 'notes', 'attachment_path']);
        });
    }
};
