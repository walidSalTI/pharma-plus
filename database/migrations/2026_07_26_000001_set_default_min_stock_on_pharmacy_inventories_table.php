<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_inventories', function (Blueprint $table) {
            $table->integer('min_stock')->default(10)->change();
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_inventories', function (Blueprint $table) {
            $table->integer('min_stock')->nullable(false)->change();
        });
    }
};
