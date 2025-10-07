<?php

return [
    'label' => 'Użytkownik',
    'plural' => 'Użytkownicy',
    'fields' => [
        'name' => [
            'label' => 'Nazwa',
            'description' => 'Pełna nazwa użytkownika.',
        ],
        'email' => [
            'label' => 'Adres e-mail',
            'description' => 'Główny adres e-mail konta.',
        ],
        'email_verified_at' => [
            'label' => 'Adres e-mail zweryfikowano',
            'description' => 'Czas potwierdzenia adresu e-mail.',
        ],
        'password' => [
            'label' => 'Hasło',
            'description' => 'Hasło konta przechowywane w bezpieczny sposób.',
        ],
        'signatures' => [
            'label' => 'Podpisy',
            'description' => 'Zestaw dostępnych podpisów.',
        ],
        'signature' => [
            'label' => 'Podpis',
            'description' => 'Wybrany podpis użytkownika.',
        ],
        'stamps' => [
            'label' => 'Pieczęcie',
            'description' => 'Pieczęcie przypisane użytkownikowi.',
        ],
        'created_at' => [
            'label' => 'Utworzono',
            'description' => 'Data i godzina utworzenia rekordu użytkownika.',
        ],
        'updated_at' => [
            'label' => 'Zaktualizowano',
            'description' => 'Data i godzina ostatniej aktualizacji rekordu.',
        ],
        'deleted_at' => [
            'label' => 'Usunięto',
            'description' => 'Data i godzina usunięcia użytkownika.',
        ],
    ],
];
