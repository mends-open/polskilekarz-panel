<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Entity;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'user_id' => User::factory(),
            'entity_id' => Entity::factory(),
        ];
    }
}
