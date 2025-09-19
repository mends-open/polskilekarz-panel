<?php

namespace Database\Factories;

use App\Models\Entity;
use App\Models\EntityUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EntityUserFactory extends Factory
{
    protected $model = EntityUser::class;

    public function definition(): array
    {
        return [
            'entity_id' => Entity::factory(),
            'user_id' => User::factory(),
        ];
    }
}
