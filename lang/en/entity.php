<?php

return [
    'label' => 'Entity',
    'plural' => 'Entities',
    'fields' => [
        'name' => [
            'label' => 'Name',
            'description' => 'Display name used for the entity.',
        ],
        'headers' => [
            'label' => 'Headers',
            'description' => 'Collection of header templates available for the entity.',
        ],
        'header' => [
            'label' => 'Header',
            'description' => 'Single header template that can be attached to documents.',
        ],
        'footers' => [
            'label' => 'Footers',
            'description' => 'Collection of footer templates available for the entity.',
        ],
        'footer' => [
            'label' => 'Footer',
            'description' => 'Single footer template that can be attached to documents.',
        ],
        'stamps' => [
            'label' => 'Stamps',
            'description' => 'Stamp graphics configured for the entity.',
        ],
        'logos' => [
            'label' => 'Logos',
            'description' => 'Logotypes configured for the entity.',
        ],
        'created_at' => [
            'label' => 'Created at',
            'description' => 'Date and time when the entity record was created.',
        ],
        'updated_at' => [
            'label' => 'Updated at',
            'description' => 'Date and time of the last update.',
        ],
        'deleted_at' => [
            'label' => 'Deleted at',
            'description' => 'Date and time when the entity was deleted.',
        ],
    ],
];
