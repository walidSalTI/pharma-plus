<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $csv = fopen(database_path('seeders/csv/products.csv'), 'r');
        fgetcsv($csv);

        while (($row = fgetcsv($csv)) !== false) {
            Product::create([
                'id' => $row[0],
                'name' => $row[1],
                'barcode' => $row[2] ?: null,
                'image' => $row[3] ?: null,
                'type' => $row[4] ?? 'medication',
                'added_by_pharmacy_id' => $row[5] ?: null,
            ]);
        }

        fclose($csv);
    }
}
