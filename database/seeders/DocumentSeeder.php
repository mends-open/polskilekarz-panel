<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        Document::factory()
            ->count(5)
            ->for($patient)
            ->for($user)
            ->create();
    }
}
