<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Pharmacist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pharmacist>
 */
class PharmacistFactory extends Factory
{
    protected $model = Pharmacist::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'syndicate_card' => 'syndicate_cards/test-card.jpg',
        ];
    }
}
