<?php

return [
    'actions' => [
        'send' => 'Send',
        'open' => 'Open',
    ],
    'invoices_table' => [
        'heading' => 'Invoices',
        'actions' => [
            'send_latest' => [
                'modal' => [
                    'heading' => 'Send latest invoice link?',
                    'description' => 'We will send the latest invoice link to the active Chatwoot conversation.',
                ],
            ],
            'duplicate' => [
                'label' => 'Duplicate',
            ],
            'send' => [
                'label' => 'Send',
                'modal' => [
                    'heading' => 'Send invoice link?',
                    'description' => 'We will send this invoice link to the current Chatwoot conversation.',
                ],
            ],
            'open' => [
                'label' => 'Open',
            ],
        ],
    ],
    'customer_infolist' => [
        'heading' => 'Customer',
        'actions' => [
            'send_portal_link' => [
                'label' => 'Send portal link',
                'modal' => [
                    'heading' => 'Send portal link?',
                    'description' => 'We will create a Stripe customer portal session and send the short link in Chatwoot.',
                ],
            ],
            'open_portal' => 'Open portal',
        ],
        'placeholders' => [
            'name' => 'No name',
            'created' => 'No created',
            'email' => 'No email',
            'phone' => 'No phone',
            'country' => 'No country',
        ],
        'labels' => [
            'country' => 'Country',
        ],
        'notifications' => [
            'missing_customer' => [
                'title' => 'Missing Stripe customer',
                'body' => 'We could not find the Stripe customer. Please select a customer first.',
            ],
            'missing_context' => [
                'title' => 'Missing Chatwoot context',
                'body' => 'We need a Chatwoot conversation to send the portal link. Please open this widget from a Chatwoot conversation.',
            ],
            'generating_portal' => [
                'title' => 'Generating portal link',
                'body' => 'We are generating a Stripe customer portal session and will send the link shortly.',
            ],
            'open_portal_failed' => [
                'title' => 'Failed to open portal',
                'body' => 'We were unable to open the customer portal. Please try again.',
            ],
        ],
    ],
    'latest_invoice_infolist' => [
        'heading' => 'Latest Invoice',
        'actions' => [
            'create_new' => 'Create New',
            'send_latest' => 'Send Latest',
            'open_latest' => 'Open Latest',
        ],
        'fields' => [
            'total' => 'Total',
            'amount_paid' => 'Amount Paid',
            'amount_remaining' => 'Amount Remaining',
        ],
        'modals' => [
            'create_invoice' => 'Create invoice',
        ],
    ],
    'latest_invoice_lines_table' => [
        'heading' => 'Latest Invoice Items',
        'columns' => [
            'description' => 'Description',
            'unit_price' => 'Unit Price',
            'quantity' => 'Qty',
            'subtotal' => 'Subtotal',
        ],
        'actions' => [
            'duplicate' => 'Duplicate',
        ],
        'modals' => [
            'duplicate_latest' => 'Duplicate latest invoice',
        ],
    ],
    'payments_table' => [
        'heading' => 'Payments',
        'actions' => [
            'open_receipt' => 'Open Receipt',
        ],
    ],
    'invoice_form' => [
        'submit' => 'Create invoice',
        'notifications' => [
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
        ],
    ],
    'notifications' => [
        'create_customer' => [
            'missing_context' => [
                'title' => 'Missing Chatwoot context',
                'body' => 'We need a Chatwoot contact to create a Stripe customer. Please open this widget from a Chatwoot conversation.',
            ],
            'load_contact_failed' => [
                'title' => 'Failed to load Chatwoot contact',
                'body' => 'We were unable to load the Chatwoot contact details. Please try again.',
            ],
            'failed' => [
                'title' => 'Failed to create Stripe customer',
                'body' => 'We were unable to create a Stripe customer from the Chatwoot contact. Please try again.',
            ],
            'success' => [
                'title' => 'Stripe customer created',
                'body' => 'A Stripe customer was created from the Chatwoot contact.',
            ],
        ],
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
];
