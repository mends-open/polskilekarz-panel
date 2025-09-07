<?php

return [
    'label' => 'Wizyta',
    'plural' => 'Wizyty',
    'type' => [
        'unspecified' => 'Nieokreślona',
        'primary_care' => 'Podstawowa opieka',
        'psychiatric' => 'Psychiatryczna',
        'psychological' => 'Psychologiczna',
        'prescription' => 'Recepta',
        'documentation' => 'Dokumentacja',
    ],
    'fields' => [
        'patient_id' => 'Pacjent',
        'user_id' => 'Użytkownik',
        'type' => 'Typ',
        'duration' => 'Czas trwania',
        'scheduled_at' => 'Zaplanowano na',
        'confirmed_at' => 'Potwierdzono o',
        'started_at' => 'Rozpoczęto o',
        'cancelled_at' => 'Anulowano o',
        'created_at' => 'Utworzono',
        'updated_at' => 'Zaktualizowano',
        'deleted_at' => 'Usunięto',
    ],
];
