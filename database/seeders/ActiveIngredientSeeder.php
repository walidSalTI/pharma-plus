<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ActiveIngredient;
use Illuminate\Database\Seeder;

class ActiveIngredientSeeder extends Seeder
{
    public function run(): void
    {
        $csv = fopen(database_path('seeders/csv/unique_composition.csv'), 'r');
        fgetcsv($csv);

        while (($row = fgetcsv($csv)) !== false) {
            ActiveIngredient::create([
                'id' => $row[0],
                'ingredient_name_en' => $row[1],
                'description' => null,
            ]);
        }

        fclose($csv);
    }
}
