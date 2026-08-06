<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Manufacture;
use Illuminate\Database\Seeder;

class ManufactureSeeder extends Seeder
{
    public function run(): void
    {
        $csv = fopen(database_path('seeders/csv/manufactures.csv'), 'r');
        fgetcsv($csv);

        while (($row = fgetcsv($csv)) !== false) {
            Manufacture::create([
                'id' => $row[0],
                'name' => $row[1],
            ]);
        }

        fclose($csv);
    }
}
