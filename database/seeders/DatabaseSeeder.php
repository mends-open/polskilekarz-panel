<?php

namespace Database\Seeders;

use App\Enums\Appointment\AppointmentType;
use App\Models\Appointment;
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
        $user = User::factory()->create();
        $entity->users()->attach($user);

        Patient::factory()->count(5)->create()->each(function (Patient $patient) use ($user) {
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

            Appointment::factory()
                ->for($patient)
                ->for($user)
                ->state(['type' => AppointmentType::General->value])
                ->create();
        });
    }
}
