<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Pharmacy;
use App\Models\PharmacyReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PharmacyReview>
 */
class PharmacyReviewFactory extends Factory
{
    protected $model = PharmacyReview::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'pharmacy_id' => Pharmacy::factory(),
            'order_id' => null,
            'rating' => fake()->numberBetween(1, 5),
            'availability_rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->sentence(),
        ];
    }
}
