<?php

return [
    'contact_infolist' => [
        'heading' => 'Contact',
        'actions' => [
            'sync_customer' => 'Sync customer',
        ],
        'placeholders' => [
            'name' => 'No name',
            'created_at' => 'No created',
            'email' => 'No email',
            'phone_number' => 'No phone',
            'country_code' => 'No created',
        ],
        'notifications' => [
            'missing_context' => [
                'title' => 'Missing Chatwoot context',
                'body' => 'We could not find the Chatwoot contact details. Please open this widget from a Chatwoot conversation.',
            ],
            'no_contact_details' => [
                'title' => 'No contact details to sync',
                'body' => 'The Chatwoot contact does not have any details to copy to the Stripe customer.',
            ],
            'syncing_customer' => [
                'title' => 'Syncing customer details',
                'body' => 'We are fetching the Chatwoot contact details and updating the Stripe customer.',
            ],
        ],
    ],
];
