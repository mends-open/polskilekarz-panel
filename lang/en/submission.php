<?php

return [
    'label' => 'Submission',
    'plural' => 'Submissions',
    'types' => [
        0 => 'Not specified',
        1 => 'Registration',
        2 => 'Prescription request',
    ],
    'fields' => [
        'patient_id' => [
            'label' => 'Patient',
            'description' => 'Patient related to the submission.',
        ],
        'user_id' => [
            'label' => 'User',
            'description' => 'Team member who handled the submission.',
        ],
        'type' => [
            'label' => 'Type',
            'description' => 'Submission category.',
        ],
        'data' => [
            'label' => 'Data',
            'description' => 'Payload submitted by the patient.',
        ],
        'created_at' => [
            'label' => 'Created at',
            'description' => 'Date and time when the submission was created.',
        ],
        'updated_at' => [
            'label' => 'Updated at',
            'description' => 'Date and time of the last update.',
        ],
        'deleted_at' => [
            'label' => 'Deleted at',
            'description' => 'Date and time when the submission was deleted.',
        ],
    ],
];
