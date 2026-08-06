<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MedicationProposal;
use App\Models\Pharmacist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicationProposal>
 */
class MedicationProposalFactory extends Factory
{
    protected $model = MedicationProposal::class;

    public function definition(): array
    {
        return [
            'pharmacist_id' => Pharmacist::factory(),
            'specialist_id' => null,
            'medication_name' => fake()->unique()->bothify('Proposed-####mg'),
            'form' => fake()->randomElement(['tablet', 'capsule', 'syrup', 'injection']),
            'image_url' => null,
            'status' => 'pending',
        ];
    }
}
