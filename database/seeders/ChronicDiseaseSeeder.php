<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ChronicDisease;
use Illuminate\Database\Seeder;

class ChronicDiseaseSeeder extends Seeder
{
    public function run(): void
    {
        $csv = fopen(database_path('seeders/csv/chronic_diseases.csv'), 'r');
        fgetcsv($csv);

        while (($row = fgetcsv($csv)) !== false) {
            ChronicDisease::create([
                'id' => $row[0],
                'name_en' => $row[1],
                'name_ar' => $row[2],
            ]);
        }

        fclose($csv);
    }
}
