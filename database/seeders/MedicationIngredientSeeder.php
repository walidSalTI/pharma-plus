<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MedicationIngredient;
use Illuminate\Database\Seeder;

class MedicationIngredientSeeder extends Seeder
{
    public function run(): void
    {
        $csv = fopen(database_path('seeders/csv/trade_name_composition.csv'), 'r');
        fgetcsv($csv);

        while (($row = fgetcsv($csv)) !== false) {
            MedicationIngredient::create([
                'medication_id' => $row[1],
                'active_ingredient_id' => $row[2],
                'active_ratio' => $row[3] ?: null,
            ]);
        }

        fclose($csv);
    }
}
