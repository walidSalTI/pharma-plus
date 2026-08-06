<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Medication;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medication>
 */
class MedicationFactory extends Factory
{
    protected $model = Medication::class;

    public function definition(): array
    {
        $product = Product::create([
            'name' => fake()->unique()->bothify('Med-####'),
            'type' => 'medication',
        ]);

        return [
            'product_id' => $product->id,
            'form' => fake()->randomElement(['tablet', 'capsule', 'syrup', 'injection', 'cream']),
            'arabic_form' => null,
        ];
    }
}
