<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_inventories', function (Blueprint $table) {
            $table->dropIndex(['expiration_date']);
            $table->dropColumn(['wholesale_price', 'expiration_date']);
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_inventories', function (Blueprint $table) {
            $table->decimal('wholesale_price', 10, 2)->default(0)->after('price');
            $table->date('expiration_date')->nullable()->index()->after('stock');
        });
    }
};
