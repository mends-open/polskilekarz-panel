<?php

namespace Database\Factories;

use App\Models\Entry;
use App\Models\EntryMedication;
use App\Models\EmaActiveSubstance;
use Illuminate\Database\Eloquent\Factories\Factory;

class EntryMedicationFactory extends Factory
{
    protected $model = EntryMedication::class;

    public function definition(): array
    {
        return [
            'entry_id' => Entry::factory(),
            'medication_id' => EmaActiveSubstance::factory(),
        ];
    }
}
