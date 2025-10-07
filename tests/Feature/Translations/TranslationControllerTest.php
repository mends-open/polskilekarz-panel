<?php

namespace Tests\Feature\Translations;

use Tests\TestCase;

class TranslationControllerTest extends TestCase
{
    public function test_it_returns_translations_for_default_locale(): void
    {
        $response = $this->getJson('/translations');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'locale',
                'translations' => [
                    'document' => ['label', 'plural', 'fields'],
                    'ema_product' => ['label', 'plural', 'routes_of_administration'],
                    'entity' => ['label', 'plural', 'fields'],
                    'entry' => ['label', 'plural', 'types'],
                    'patient' => ['label', 'plural', 'genders', 'identifiers', 'fields'],
                    'submission' => ['label', 'plural', 'types', 'fields'],
                    'user' => ['label', 'plural', 'fields'],
                ],
            ])
            ->assertJsonPath('translations.entry.types.0', 'Not specified')
            ->assertJsonPath('translations.patient.fields.first_name.label', 'First name')
            ->assertJsonPath('translations.patient.fields.first_name.description', 'Patient given name.')
            ->assertJsonPath('translations.document.fields.patient_id.label', 'Patient')
            ->assertJsonPath('translations.document.fields.patient_id.description', 'Patient associated with the document.');
    }

    public function test_it_returns_translations_for_given_locale(): void
    {
        $response = $this->getJson('/translations/pl');

        $response
            ->assertOk()
            ->assertJsonPath('locale', 'pl')
            ->assertJsonPath('translations.entry.types.0', 'Nie określono')
            ->assertJsonPath('translations.patient.fields.first_name.label', 'Imię')
            ->assertJsonPath('translations.patient.fields.first_name.description', 'Imię pacjenta.');
    }
}
