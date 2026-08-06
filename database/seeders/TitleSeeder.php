<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Title;
use Illuminate\Database\Seeder;

class TitleSeeder extends Seeder
{
    public function run(): void
    {
        $csv = fopen(database_path('seeders/csv/titles.csv'), 'r');
        fgetcsv($csv);

        while (($row = fgetcsv($csv)) !== false) {
            Title::create([
                'id' => $row[0],
                'name' => $row[1],
                'category_id' => $row[2],
            ]);
        }

        fclose($csv);
    }
}
