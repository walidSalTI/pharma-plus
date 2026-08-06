<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Usage;
use Illuminate\Database\Seeder;

class UsageSeeder extends Seeder
{
    public function run(): void
    {
        $csv = fopen(database_path('seeders/csv/unique_usage.csv'), 'r');
        fgetcsv($csv);

        while (($row = fgetcsv($csv)) !== false) {
            Usage::create([
                'id' => $row[0],
                'name' => $row[1],
                'title_id' => $row[2],
            ]);
        }

        fclose($csv);
    }
}
