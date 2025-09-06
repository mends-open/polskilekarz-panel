<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Entry;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentEntryFactory extends Factory
{
    protected $model = DocumentEntry::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'entry_id' => Entry::factory(),
        ];
    }
}
