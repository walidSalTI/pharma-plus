<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryIncSeeder extends Seeder
{
    public function run(): void
    {
        $pharmacyId = 'babd5163-160c-43a8-84d8-99d1f4f01068';
        $now = Carbon::now();

        $rows = [];

        // 1000 items: medication_id 1000–1999
        // Use range(1000, 2000) if you want 1001 items (1000–2000 inclusive)
        foreach (range(1000, 1999) as $medicationId) {
            $rows[] = [
                'id' => (string) Str::uuid(),
                'pharmacy_id' => $pharmacyId,
                'medication_id' => (string) $medicationId,
                'price' => rand(5000, 45000),
                'stock' => rand(20, 150),
                'last_updated' => $now,
                'min_stock' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Insert in chunks to avoid memory/query limits
        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('pharmacy_inventories')->insert($chunk);
        }
    }
}