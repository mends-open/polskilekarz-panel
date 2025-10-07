<?php

return [
    'label' => 'Document',
    'plural' => 'Documents',
    'fields' => [
        'patient_id' => [
            'label' => 'Patient',
            'description' => 'Patient associated with the document.',
        ],
        'user_id' => [
            'label' => 'User',
            'description' => 'Team member who issued the document.',
        ],
        'created_at' => [
            'label' => 'Created at',
            'description' => 'Date and time when the document was created.',
        ],
        'updated_at' => [
            'label' => 'Updated at',
            'description' => 'Date and time of the last update.',
        ],
        'deleted_at' => [
            'label' => 'Deleted at',
            'description' => 'Date and time when the document was deleted.',
        ],
    ],
];
