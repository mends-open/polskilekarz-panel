<?php

return [
    'label' => 'Zgłoszenie',
    'plural' => 'Zgłoszenia',
    'types' => [
        0 => 'Nie określono',
        1 => 'Rejestracja',
        2 => 'Prośba o receptę',
    ],
    'fields' => [
        'patient_id' => [
            'label' => 'Pacjent',
            'description' => 'Pacjent powiązany ze zgłoszeniem.',
        ],
        'user_id' => [
            'label' => 'Użytkownik',
            'description' => 'Członek zespołu obsługujący zgłoszenie.',
        ],
        'type' => [
            'label' => 'Typ',
            'description' => 'Kategoria zgłoszenia.',
        ],
        'data' => [
            'label' => 'Dane',
            'description' => 'Dane przekazane przez pacjenta.',
        ],
        'created_at' => [
            'label' => 'Utworzono',
            'description' => 'Data i godzina utworzenia zgłoszenia.',
        ],
        'updated_at' => [
            'label' => 'Zaktualizowano',
            'description' => 'Data i godzina ostatniej aktualizacji zgłoszenia.',
        ],
        'deleted_at' => [
            'label' => 'Usunięto',
            'description' => 'Data i godzina usunięcia zgłoszenia.',
        ],
    ],
];
