<?php

namespace Database\Factories;

use App\Enums\Entry\Type as EntryType;
use App\Models\Entry;
use App\Models\Entity;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EntryFactory extends Factory
{
    protected $model = Entry::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'user_id' => User::factory(),
            'entity_id' => Entity::factory(),
            'type' => $this->faker->randomElement(EntryType::cases())->value,
            'data' => [],
        ];
    }
}
