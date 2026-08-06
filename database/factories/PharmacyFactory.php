<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Pharmacy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pharmacy>
 */
class PharmacyFactory extends Factory
{
    protected $model = Pharmacy::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company().' Pharmacy',
            'address' => fake()->address(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'support_email' => fake()->companyEmail(),
            'support_number' => fake()->phoneNumber(),
            'front_image' => 'pharmacy_images/test-pharmacy.jpg',
        ];
    }
}
