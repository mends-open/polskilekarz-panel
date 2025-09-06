<?php

namespace Database\Factories;

use App\Models\Email;
use App\Models\EmailPatient;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailPatientFactory extends Factory
{
    protected $model = EmailPatient::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'email_id' => Email::factory(),
            'primary_since' => now(),
            'message_consent_since' => now(),
        ];
    }
}

