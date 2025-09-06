<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Entity;
use App\Models\Entry;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $entity = Entity::factory()->create();
        $user = User::factory()->create();
        $entity->users()->attach($user);
        $patient = Patient::factory()->create();

        $entries = Entry::factory()
            ->count(3)
            ->for($patient)
            ->for($user)
            ->for($entity)
            ->create();

        $document = Document::factory()
            ->for($patient)
            ->for($user)
            ->create();

        $document->entries()->attach($entries);
    }
}
