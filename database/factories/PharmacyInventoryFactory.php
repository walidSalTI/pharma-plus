<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Medication;
use App\Models\Pharmacy;
use App\Models\PharmacyInventory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PharmacyInventory>
 */
class PharmacyInventoryFactory extends Factory
{
    protected $model = PharmacyInventory::class;

    public function definition(): array
    {
        return [
            'pharmacy_id' => Pharmacy::factory(),
            'medication_id' => Medication::factory(),
            'price' => fake()->randomFloat(2, 5, 500),
            'stock' => fake()->numberBetween(0, 500),
            'last_updated' => now(),
            'min_stock' => fake()->numberBetween(5, 50),
        ];
    }
}
