<?php

return [
    'label' => 'Patient',
    'plural' => 'Patients',
    'genders' => [
        0 => 'Male',
        1 => 'Female',
        2 => 'Other',
        3 => 'Unknown',
    ],
    'identifiers' => [
        0 => 'Not specified',
        1 => 'Identity document',
        2 => 'Passport',
        3 => "Driver's license",
        4 => 'European Health Insurance Card',
        5 => 'PESEL',
        6 => 'BSN',
        7 => 'ID Nr',
        8 => 'BIS number',
        9 => 'NIR',
        10 => 'NUSS',
        11 => 'Codice Fiscale',
    ],
    'fields' => [
        'first_name' => [
            'label' => 'First name',
            'description' => 'Patient given name.',
        ],
        'last_name' => [
            'label' => 'Last name',
            'description' => 'Patient family name.',
        ],
        'birth_date' => [
            'label' => 'Birth date',
            'description' => 'Patient date of birth.',
        ],
        'gender' => [
            'label' => 'Gender',
            'description' => 'Administrative gender of the patient.',
        ],
        'addresses' => [
            'label' => 'Addresses',
            'description' => 'List of addresses stored for the patient.',
        ],
        'line1' => [
            'label' => 'Line 1',
            'description' => 'Primary address line.',
        ],
        'city' => [
            'label' => 'City',
            'description' => 'City or locality of the address.',
        ],
        'postal_code' => [
            'label' => 'Postal code',
            'description' => 'Postal code of the address.',
        ],
        'country' => [
            'label' => 'Country',
            'description' => 'Country of the address.',
        ],
        'identifiers' => [
            'label' => 'Identifiers',
            'description' => 'Identifiers assigned to the patient.',
        ],
        'created_at' => [
            'label' => 'Created at',
            'description' => 'Date and time when the patient record was created.',
        ],
        'updated_at' => [
            'label' => 'Updated at',
            'description' => 'Date and time of the last update.',
        ],
        'deleted_at' => [
            'label' => 'Deleted at',
            'description' => 'Date and time when the patient was deleted.',
        ],
    ],
];
