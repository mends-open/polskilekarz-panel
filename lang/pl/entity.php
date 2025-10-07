<?php

return [
    'label' => 'Podmiot',
    'plural' => 'Podmioty',
    'fields' => [
        'name' => [
            'label' => 'Nazwa',
            'description' => 'Wyświetlana nazwa podmiotu.',
        ],
        'headers' => [
            'label' => 'Nagłówki',
            'description' => 'Zestaw szablonów nagłówków dostępnych dla podmiotu.',
        ],
        'header' => [
            'label' => 'Nagłówek',
            'description' => 'Pojedynczy szablon nagłówka dodawany do dokumentów.',
        ],
        'footers' => [
            'label' => 'Stopki',
            'description' => 'Zestaw szablonów stopek dostępnych dla podmiotu.',
        ],
        'footer' => [
            'label' => 'Stopka',
            'description' => 'Pojedynczy szablon stopki dodawany do dokumentów.',
        ],
        'stamps' => [
            'label' => 'Pieczęcie',
            'description' => 'Grafiki pieczęci skonfigurowane dla podmiotu.',
        ],
        'logos' => [
            'label' => 'Logotypy',
            'description' => 'Logotypy przypisane do podmiotu.',
        ],
        'created_at' => [
            'label' => 'Utworzono',
            'description' => 'Data i godzina utworzenia rekordu podmiotu.',
        ],
        'updated_at' => [
            'label' => 'Zaktualizowano',
            'description' => 'Data i godzina ostatniej aktualizacji.',
        ],
        'deleted_at' => [
            'label' => 'Usunięto',
            'description' => 'Data i godzina usunięcia podmiotu.',
        ],
    ],
];
