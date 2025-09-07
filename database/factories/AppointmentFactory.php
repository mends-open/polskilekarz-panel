<?php

namespace Database\Factories;

use App\Enums\Appointment\Type;
use App\Models\Appointment;
use App\Models\Entity;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $scheduled = $this->faker->dateTime();

        return [
            'patient_id' => Patient::factory(),
            'user_id' => User::factory(),
            'entity_id' => Entity::factory(),
            'type' => $this->faker->randomElement(Type::cases())->value,
            'duration' => $this->faker->numberBetween(15, 60),
            'scheduled_at' => $scheduled,
            'confirmed_at' => null,
            'started_at' => null,
            'cancelled_at' => null,
        ];
    }
}
