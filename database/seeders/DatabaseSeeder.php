<?php

namespace Database\Seeders;

use App\Models\Email;
use App\Models\Entity;
use App\Models\Patient;
use App\Models\Phone;
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
        $users = User::factory()->count(3)->create();
        $entity->users()->attach($users);

        Patient::factory()->count(10)->create()->each(function (Patient $patient) use ($users, $entity) {
            $email = Email::factory()->create();
            $patient->emails()->attach($email, [
                'primary_since' => now(),
                'message_consent_since' => now(),
            ]);

            $phone = Phone::factory()->create();
            $patient->phones()->attach($phone, [
                'primary_since' => now(),
                'call_consent_since' => now(),
                'sms_consent_since' => now(),
                'whatsapp_consent_since' => now(),
            ]);

        });
    }
}
