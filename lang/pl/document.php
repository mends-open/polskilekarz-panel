<?php

return [
    'label' => 'Dokument',
    'plural' => 'Dokumenty',
    'fields' => [
        'patient_id' => [
            'label' => 'Pacjent',
            'description' => 'Pacjent powiązany z dokumentem.',
        ],
        'user_id' => [
            'label' => 'Użytkownik',
            'description' => 'Członek zespołu, który utworzył dokument.',
        ],
        'created_at' => [
            'label' => 'Utworzono',
            'description' => 'Data i godzina utworzenia dokumentu.',
        ],
        'updated_at' => [
            'label' => 'Zaktualizowano',
            'description' => 'Data i godzina ostatniej aktualizacji.',
        ],
        'deleted_at' => [
            'label' => 'Usunięto',
            'description' => 'Data i godzina usunięcia dokumentu.',
        ],
    ],
];
