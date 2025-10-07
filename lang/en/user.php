<?php

return [
    'label' => 'User',
    'plural' => 'Users',
    'fields' => [
        'name' => [
            'label' => 'Name',
            'description' => 'Full name of the user.',
        ],
        'email' => [
            'label' => 'Email address',
            'description' => 'Primary email address for the account.',
        ],
        'email_verified_at' => [
            'label' => 'Email verified at',
            'description' => 'Timestamp when the email address was verified.',
        ],
        'password' => [
            'label' => 'Password',
            'description' => 'Account password stored securely.',
        ],
        'signatures' => [
            'label' => 'Signatures',
            'description' => 'Collection of available signatures.',
        ],
        'signature' => [
            'label' => 'Signature',
            'description' => 'Selected signature.',
        ],
        'stamps' => [
            'label' => 'Stamps',
            'description' => 'Stamps assigned to the user.',
        ],
        'created_at' => [
            'label' => 'Created at',
            'description' => 'Date and time when the user record was created.',
        ],
        'updated_at' => [
            'label' => 'Updated at',
            'description' => 'Date and time of the last update.',
        ],
        'deleted_at' => [
            'label' => 'Deleted at',
            'description' => 'Date and time when the user was deleted.',
        ],
    ],
];
