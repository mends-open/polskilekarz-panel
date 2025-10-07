<?php

return [
    'label' => 'Pacjent',
    'plural' => 'Pacjenci',
    'genders' => [
        0 => 'Mężczyzna',
        1 => 'Kobieta',
        2 => 'Inna',
        3 => 'Nieznana',
    ],
    'identifiers' => [
        0 => 'Nie określono',
        1 => 'Dokument tożsamości',
        2 => 'Paszport',
        3 => 'Prawo jazdy',
        4 => 'Europejska Karta Ubezpieczenia Zdrowotnego',
        5 => 'PESEL',
        6 => 'BSN',
        7 => 'IdNr',
        8 => 'Numer BIS',
        9 => 'NIR',
        10 => 'NUSS',
        11 => 'Codice Fiscale',
    ],
    'fields' => [
        'first_name' => [
            'label' => 'Imię',
            'description' => 'Imię pacjenta.',
        ],
        'last_name' => [
            'label' => 'Nazwisko',
            'description' => 'Nazwisko pacjenta.',
        ],
        'birth_date' => [
            'label' => 'Data urodzenia',
            'description' => 'Data urodzenia pacjenta.',
        ],
        'gender' => [
            'label' => 'Płeć',
            'description' => 'Administracyjna płeć pacjenta.',
        ],
        'addresses' => [
            'label' => 'Adresy',
            'description' => 'Lista zapisanych adresów pacjenta.',
        ],
        'line1' => [
            'label' => 'Linia 1',
            'description' => 'Pierwsza linia adresu.',
        ],
        'city' => [
            'label' => 'Miasto',
            'description' => 'Miasto lub miejscowość w adresie.',
        ],
        'postal_code' => [
            'label' => 'Kod pocztowy',
            'description' => 'Kod pocztowy adresu.',
        ],
        'country' => [
            'label' => 'Kraj',
            'description' => 'Kraj podany w adresie.',
        ],
        'identifiers' => [
            'label' => 'Identyfikatory',
            'description' => 'Identyfikatory przypisane pacjentowi.',
        ],
        'created_at' => [
            'label' => 'Utworzono',
            'description' => 'Data i godzina utworzenia rekordu pacjenta.',
        ],
        'updated_at' => [
            'label' => 'Zaktualizowano',
            'description' => 'Data i godzina ostatniej aktualizacji.',
        ],
        'deleted_at' => [
            'label' => 'Usunięto',
            'description' => 'Data i godzina usunięcia pacjenta.',
        ],
    ],
];
