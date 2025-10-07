<?php

return [
    'chatwoot' => [
        'contact_infolist' => [
            'missing_context' => [
                'title' => 'Missing Chatwoot context',
                'body' => 'We could not find the Chatwoot contact details. Please open this widget from a Chatwoot conversation.',
            ],
            'load_failed' => [
                'title' => 'Failed to load Chatwoot contact',
                'body' => 'We were unable to load the Chatwoot contact details. Please try again.',
            ],
            'create_customer_failed' => [
                'title' => 'Failed to create Stripe customer',
                'body' => 'We were unable to create a Stripe customer from the Chatwoot contact. Please try again.',
            ],
            'customer_created' => [
                'title' => 'Stripe customer created',
                'body' => 'A Stripe customer was created from the Chatwoot contact details.',
            ],
            'nothing_to_sync' => [
                'title' => 'No contact details to sync',
                'body' => 'The Chatwoot contact does not have any details to copy to the Stripe customer.',
            ],
            'syncing' => [
                'title' => 'Syncing customer details',
                'body' => 'We are fetching the Chatwoot contact details and updating the Stripe customer.',
            ],
        ],
    ],
    'stripe' => [
        'customer_infolist' => [
            'missing_customer' => [
                'title' => 'Missing Stripe customer',
                'body' => 'We could not find the Stripe customer. Please select a customer first.',
            ],
            'missing_chatwoot_context' => [
                'title' => 'Missing Chatwoot context',
                'body' => 'We need a Chatwoot conversation to send the portal link. Please open this widget from a Chatwoot conversation.',
            ],
            'generating_portal_link' => [
                'title' => 'Generating portal link',
                'body' => 'We are generating a Stripe customer portal session and will send the link shortly.',
            ],
            'open_portal_failed' => [
                'title' => 'Failed to open portal',
                'body' => 'We were unable to open the customer portal. Please try again.',
            ],
        ],
        'interacts_with_invoices' => [
            'invoice_link_unavailable' => [
                'title' => 'Invoice link unavailable',
                'body' => 'We could not find a hosted invoice URL on the invoice.',
            ],
            'missing_chatwoot_context' => [
                'title' => 'Missing Chatwoot context',
                'body' => 'Unable to send the invoice link because the Chatwoot context is incomplete.',
            ],
            'sending_invoice_link' => [
                'title' => 'Sending invoice link',
                'body' => 'We are preparing the invoice link and will send it to the conversation shortly.',
            ],
        ],
        'invoice_form' => [
            'no_products' => [
                'title' => 'No products selected',
                'body' => 'Please select at least one product and price to include on the invoice.',
            ],
            'mixed_currencies' => [
                'title' => 'Mixed currencies selected',
                'body' => 'All selected products must use the same currency. Please adjust your selection and try again.',
            ],
            'creating_invoice' => [
                'title' => 'Creating invoice',
                'body' => 'We are preparing the invoice in Stripe. You will be notified once it is ready.',
            ],
            'missing_chatwoot_context' => [
                'title' => 'Missing Chatwoot context',
                'body' => 'We need a Chatwoot contact to create a Stripe customer. Please open this widget from a Chatwoot conversation.',
            ],
        ],
    ],
    'jobs' => [
        'stripe' => [
            'create_customer_portal_session_link' => [
                'success' => [
                    'title' => 'Customer portal link generated',
                    'body' => 'The customer portal link was generated and will be sent shortly.',
                ],
                'failed' => [
                    'title' => 'Failed to create customer portal link',
                    'body' => 'We were unable to generate the customer portal link. Please try again.',
                ],
            ],
            'sync_customer_from_chatwoot_contact' => [
                'updated' => [
                    'title' => 'Stripe customer updated',
                    'body' => 'The Stripe customer was updated with the Chatwoot contact details.',
                ],
                'failed' => [
                    'title' => 'Failed to update Stripe customer',
                    'body' => 'We were unable to sync the Stripe customer with the Chatwoot contact. Please try again.',
                ],
            ],
            'create_invoice' => [
                'success' => [
                    'title' => 'Invoice created',
                    'body' => 'Invoice :invoice has been created.',
                    'with_total' => [
                        'body' => 'Invoice :invoice for :amount has been created.',
                    ],
                ],
                'failed' => [
                    'title' => 'Failed to create invoice',
                    'body' => 'We were unable to create the invoice in Stripe. Please try again.',
                ],
            ],
        ],
        'chatwoot' => [
            'create_invoice_short_link' => [
                'success' => [
                    'title' => 'Invoice link shortened',
                    'body' => 'The invoice link has been shortened and will be delivered shortly.',
                ],
                'failed' => [
                    'title' => 'Failed to shorten invoice link',
                    'body' => 'We were unable to create a short link for the invoice. Please try again.',
                ],
            ],
            'send_invoice_short_link_message' => [
                'success' => [
                    'title' => 'Invoice link sent',
                    'body' => 'The shortened invoice link was sent to the Chatwoot conversation.',
                ],
                'failed' => [
                    'title' => 'Failed to send invoice link',
                    'body' => 'We were unable to send the invoice link to the Chatwoot conversation. Please try again.',
                ],
            ],
            'send_customer_portal_link_message' => [
                'success' => [
                    'title' => 'Customer portal link sent',
                    'body' => 'The customer portal link was sent to the Chatwoot conversation.',
                ],
                'failed' => [
                    'title' => 'Failed to send customer portal link',
                    'body' => 'We were unable to send the customer portal link to the Chatwoot conversation. Please try again.',
                ],
            ],
        ],
    ],
];
