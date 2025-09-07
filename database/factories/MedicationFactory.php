<?php

namespace Database\Factories;

use App\Models\ActiveSubstance;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicationFactory extends Factory
{
    protected $model = ActiveSubstance::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
        ];
    }
}
