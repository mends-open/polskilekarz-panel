<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientPhone;
use App\Models\Phone;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientPhoneFactory extends Factory
{
    protected $model = PatientPhone::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'phone_id' => Phone::factory(),
            'primary_since' => now(),
            'call_consent_since' => now(),
            'sms_consent_since' => now(),
            'whatsapp_consent_since' => now(),
        ];
    }
}
