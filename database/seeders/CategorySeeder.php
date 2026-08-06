<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $csv = fopen(database_path('seeders/csv/category.csv'), 'r');
        fgetcsv($csv);

        while (($row = fgetcsv($csv)) !== false) {
            Category::create([
                'id' => $row[0],
                'name' => $row[1],
            ]);
        }

        fclose($csv);
    }
}
