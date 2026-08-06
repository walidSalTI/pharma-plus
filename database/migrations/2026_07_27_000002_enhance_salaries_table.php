<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('amount');
        });

        Schema::table('salaries', function (Blueprint $table) {
            $table->foreignUuid('user_id')->nullable()->change();
            $table->string('recipient_name')->after('user_id');
            $table->decimal('base_amount', 10, 2)->after('recipient_name');
            $table->decimal('bonus', 10, 2)->default(0)->after('base_amount');
            $table->decimal('deductions', 10, 2)->default(0)->after('bonus');
            $table->decimal('net_amount', 10, 2)->after('deductions');
            $table->string('salary_period')->after('net_amount')->comment('e.g., 2026-07');
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'apps'])->default('cash')->after('paid_at');
            $table->text('notes')->nullable()->after('payment_method');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['pharmacy_id', 'salary_period']);
            $table->index(['pharmacy_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['pharmacy_id', 'salary_period']);
            $table->dropIndex(['pharmacy_id', 'paid_at']);
            $table->dropColumn([
                'recipient_name', 'base_amount', 'bonus', 'deductions',
                'net_amount', 'salary_period', 'payment_method', 'notes',
            ]);
        });

        Schema::table('salaries', function (Blueprint $table) {
            $table->decimal('amount', 10, 2);
            $table->foreignUuid('user_id')->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
