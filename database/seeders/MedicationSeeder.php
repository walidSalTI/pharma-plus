<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Medication;
use Illuminate\Database\Seeder;

class MedicationSeeder extends Seeder
{
    public function run(): void
    {
        $csv = fopen(database_path('seeders/csv/unique_trade_name.csv'), 'r');
        fgetcsv($csv);

        while (($row = fgetcsv($csv)) !== false) {
            $manufactureId = $row[2];
            if (str_contains($manufactureId, ';')) {
                $manufactureId = explode(';', $manufactureId)[0];
            }
            if ($manufactureId === '000' || $manufactureId === '0') {
                $manufactureId = null;
            }

            $usageId = $row[5] ?? null;
            if ($usageId === '0') {
                $usageId = null;
            }

            Medication::create([
                'id' => $row[0],
                'product_id' => $row[1],
                'manufacture_id' => $manufactureId,
                'form' => $row[3],
                'arabic_form' => $row[4],
                'usage_id' => $usageId,
                'status' => $row[6] ?: 'accepted',
                'rejection_reason' => $row[7] ?: null,
            ]);
        }

        fclose($csv);
    }
}
