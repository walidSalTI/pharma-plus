<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ActiveIngredientsChronicDisease;
use Illuminate\Database\Seeder;

class ActiveIngredientsChronicDiseaseSeeder extends Seeder
{
    public function run(): void
    {
        $csv = fopen(database_path('seeders/csv/disease_composition_conflicts.csv'), 'r');
        fgetcsv($csv);

        while (($row = fgetcsv($csv)) !== false) {
            ActiveIngredientsChronicDisease::create([
                'chronic_disease_id' => $row[0],
                'active_ingredient_id' => $row[1],
            ]);
        }

        fclose($csv);
    }
}
