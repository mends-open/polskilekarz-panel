<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Entity;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $entity = Entity::factory()->create();
        $user = User::factory()->create();
        $entity->users()->attach($user);
        $patient = Patient::factory()->create();

        Document::factory()
            ->count(5)
            ->for($patient)
            ->for($entity)
            ->for($user)
            ->create();
    }
}
