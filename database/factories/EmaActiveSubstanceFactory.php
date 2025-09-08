<?php

namespace Database\Factories;

use App\Models\EmaActiveSubstance;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmaActiveSubstanceFactory extends Factory
{
    protected $model = EmaActiveSubstance::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
        ];
    }
}
