<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CompositionInteraction;
use Illuminate\Database\Seeder;

class CompositionInteractionSeeder extends Seeder
{
    public function run(): void
    {
        $csv = fopen(database_path('seeders/csv/interaction.csv'), 'r');
        fgetcsv($csv);

        while (($row = fgetcsv($csv)) !== false) {
            CompositionInteraction::create([
                'composition_id' => $row[1],
                'interaction_composition_id' => $row[3],
                'interaction_effect' => $row[5],
            ]);
        }

        fclose($csv);
    }
}
