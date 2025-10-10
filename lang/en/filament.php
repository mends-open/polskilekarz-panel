<?php

return [
    'widgets' => [
        'common' => [
            'placeholders' => [
                'blank' => ' ',
                'id' => 'No ID',
                'name' => 'No name',
                'created_at' => 'No creation date',
                'email' => 'No email',
                'phone' => 'No phone',
                'country' => 'No country',
                'status' => 'No status',
                'due_date' => 'No due date',
                'total' => 'No total',
                'amount_paid' => 'No amount paid',
                'amount_remaining' => 'No amount remaining',
                'currency' => 'No currency',
                'number' => 'No number',
                'description' => 'No description',
                'unit_price' => 'No unit price',
                'quantity' => 'No quantity',
                'subtotal' => 'No subtotal',
                'payment_method' => 'No payment method',
                'price' => 'No price',
                'product' => 'No product',
                'metadata' => 'No metadata',
            ],
        ],
        'chatwoot' => [
            'contact_infolist' => [
                'section' => [
                    'title' => 'Contact',
                ],
                'actions' => [
                    'sync_customer_from_contact' => [
                        'label' => 'Sync customer',
                    ],
                ],
                'fields' => [
                    'id' => [
                        'label' => 'ID',
                        'placeholder' => 'No ID',
                    ],
                    'name' => [
                        'label' => 'Name',
                    ],
                    'created_at' => [
                        'label' => 'Created',
                    ],
                    'email' => [
                        'label' => 'Email',
                    ],
                    'phone_number' => [
                        'label' => 'Phone',
                    ],
                    'country_code' => [
                        'label' => 'Country',
                    ],
                ],
            ],
        ],
        'cloudflare' => [
            'links_table' => [
                'heading' => 'Short links',
                'empty_state' => [
                    'heading' => 'No short links',
                    'description' => 'We have not created any short links for this contact yet.',
                ],
                'columns' => [
                    'slug' => [
                        'label' => 'Slug',
                    ],
                    'short_url' => [
                        'label' => 'Short link',
                    ],
                    'url' => [
                        'label' => 'Destination',
                    ],
                    'entity_type' => [
                        'label' => 'Object',
                    ],
                    'entity_identifier' => [
                        'label' => 'Object ID',
                    ],
                    'created_at' => [
                        'label' => 'Created',
                    ],
                    'created_at_exact' => [
                        'label' => 'Created at',
                    ],
                ],
            ],
            'link_entries_table' => [
                'heading' => 'Short link activity',
                'empty_state' => [
                    'heading' => 'No link activity',
                    'description' => 'We have not recorded any short link visits for this contact yet.',
                ],
                'columns' => [
                    'slug' => [
                        'label' => 'Slug',
                    ],
                    'short_url' => [
                        'label' => 'Short link',
                    ],
                    'url' => [
                        'label' => 'Destination',
                    ],
                    'entity_type' => [
                        'label' => 'Object',
                    ],
                    'entity_identifier' => [
                        'label' => 'Object ID',
                    ],
                    'request_url' => [
                        'label' => 'Request URL',
                    ],
                    'request_method' => [
                        'label' => 'Method',
                    ],
                    'request_ip' => [
                        'label' => 'IP address',
                    ],
                    'response_status' => [
                        'label' => 'Status',
                    ],
                    'timestamp' => [
                        'label' => 'Visited',
                    ],
                    'timestamp_exact' => [
                        'label' => 'Visited at',
                    ],
                ],
            ],
            'enums' => [
                'entity_types' => [
                    'invoice' => 'Invoice',
                    'billing_portal' => 'Billing portal',
                    'customer' => 'Customer',
                    'link' => 'Link',
                ],
            ],
            'metadata_keys' => [
                'chatwoot_account_id' => 'Chatwoot account',
                'chatwoot_conversation_id' => 'Chatwoot conversation',
                'chatwoot_inbox_id' => 'Chatwoot inbox',
                'chatwoot_contact_id' => 'Chatwoot contact',
                'chatwoot_user_id' => 'Chatwoot user',
                'user_id' => 'User',
                'stripe_customer_id' => 'Stripe customer',
                'stripe_invoice_id' => 'Stripe invoice',
                'stripe_billing_portal_session' => 'Billing portal session',
            ],
        ],
        'stripe' => [
            'enums' => [
                'invoice_statuses' => [
                    'draft' => 'Draft',
                    'open' => 'Open',
                    'paid' => 'Paid',
                    'uncollectible' => 'Uncollectible',
                    'void' => 'Void',
                ],
                'payment_intent_statuses' => [
                    'canceled' => 'Canceled',
                    'processing' => 'Processing',
                    'requires_action' => 'Requires action',
                    'requires_capture' => 'Requires capture',
                    'requires_confirmation' => 'Requires confirmation',
                    'requires_payment_method' => 'Requires payment method',
                    'succeeded' => 'Succeeded',
                ],
            ],
            'customer_infolist' => [
                'section' => [
                    'title' => 'Customer',
                ],
                'actions' => [
                    'send_portal_link' => [
                        'label' => 'Send portal link',
                        'modal' => [
                            'heading' => 'Send portal link?',
                            'description' => 'We will create a Stripe customer portal session and send the short link in Chatwoot.',
                        ],
                    ],
                    'open_portal' => [
                        'label' => 'Open portal',
                    ],
                ],
                'fields' => [
                    'id' => [
                        'label' => 'ID',
                    ],
                    'name' => [
                        'label' => 'Name',
                    ],
                    'created' => [
                        'label' => 'Created',
                    ],
                    'email' => [
                        'label' => 'Email',
                    ],
                    'phone' => [
                        'label' => 'Phone',
                    ],
                    'address_country' => [
                        'label' => 'Country',
                    ],
                ],
            ],
            'latest_invoice_infolist' => [
                'section' => [
                    'title' => 'Latest Invoice',
                ],
                'actions' => [
                    'create_invoice' => [
                        'label' => 'Create New',
                        'modal' => [
                            'heading' => 'Create invoice',
                        ],
                    ],
                    'send_latest' => [
                        'label' => 'Send Latest',
                        'modal' => [
                            'heading' => 'Send latest invoice link?',
                            'description' => 'We will send the latest invoice link to the active Chatwoot conversation.',
                        ],
                    ],
                    'open_latest' => [
                        'label' => 'Open Latest',
                    ],
                ],
                'fields' => [
                    'id' => [
                        'label' => 'ID',
                    ],
                    'status' => [
                        'label' => 'Status',
                    ],
                    'created' => [
                        'label' => 'Created',
                    ],
                    'due_date' => [
                        'label' => 'Due date',
                    ],
                    'total' => [
                        'label' => 'Total',
                    ],
                    'amount_paid' => [
                        'label' => 'Amount Paid',
                    ],
                    'amount_remaining' => [
                        'label' => 'Amount Remaining',
                    ],
                    'currency' => [
                        'label' => 'Currency',
                    ],
                ],
            ],
            'invoices_table' => [
                'heading' => 'Invoices',
                'empty_state' => [
                    'heading' => 'No invoices',
                    'description' => 'This customer has no invoices yet.',
                ],
                'columns' => [
                    'id' => [
                        'label' => 'ID',
                    ],
                    'number' => [
                        'label' => 'Number',
                    ],
                    'total' => [
                        'label' => 'Total',
                    ],
                    'status' => [
                        'label' => 'Status',
                    ],
                    'currency' => [
                        'label' => 'Currency',
                    ],
                    'created' => [
                        'label' => 'Created',
                    ],
                    'lines' => [
                        'description' => [
                            'label' => 'Description',
                        ],
                        'quantity' => [
                            'label' => 'Qty',
                            'prefix' => 'x',
                        ],
                        'amount' => [
                            'label' => 'Subtotal',
                        ],
                    ],
                ],
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
            'latest_invoice_lines_table' => [
                'heading' => 'Latest Invoice Items',
                'empty_state' => [
                    'heading' => 'No line items',
                    'description' => 'The latest invoice does not include any line items.',
                ],
                'columns' => [
                    'price' => [
                        'label' => 'Price ID',
                    ],
                    'product' => [
                        'label' => 'Product ID',
                    ],
                    'description' => [
                        'label' => 'Description',
                    ],
                    'unit_amount' => [
                        'label' => 'Unit Price',
                    ],
                    'quantity' => [
                        'label' => 'Qty',
                        'prefix' => 'x',
                    ],
                    'amount' => [
                        'label' => 'Subtotal',
                    ],
                ],
                'actions' => [
                    'duplicate' => [
                        'label' => 'Duplicate',
                        'modal' => [
                            'heading' => 'Duplicate latest invoice',
                        ],
                    ],
                ],
            ],
            'payments_table' => [
                'heading' => 'Payments',
                'empty_state' => [
                    'heading' => 'No payments',
                    'description' => 'This customer has no payments yet.',
                ],
                'columns' => [
                    'id' => [
                        'label' => 'ID',
                    ],
                    'amount' => [
                        'label' => 'Amount',
                    ],
                    'status' => [
                        'label' => 'Status',
                    ],
                    'payment_method_type' => [
                        'label' => 'Payment method',
                    ],
                    'created' => [
                        'label' => 'Created',
                    ],
                ],
                'actions' => [
                    'open_receipt' => [
                        'label' => 'Open Receipt',
                    ],
                ],
            ],
            'invoice_form' => [
                'repeater' => [
                    'label' => 'Products',
                    'validation_attribute' => 'products',
                ],
                'table_columns' => [
                    'product' => 'Product',
                    'price' => 'Price',
                    'quantity' => 'Quantity',
                    'subtotal' => 'Subtotal',
                ],
                'fields' => [
                    'product' => [
                        'label' => 'Product',
                        'placeholder' => 'Select a product',
                    ],
                    'price' => [
                        'label' => 'Price',
                        'placeholder' => 'Select a price',
                    ],
                    'quantity' => [
                        'label' => 'Quantity',
                    ],
                    'subtotal' => [
                        'label' => 'Subtotal',
                    ],
                ],
                'defaults' => [
                    'product_name' => 'Product',
                ],
                'actions' => [
                    'submit' => 'Create invoice',
                ],
            ],
        ],
    ],
    'pages' => [
        'payments' => [
            'title' => 'Payments',
            'navigation' => [
                'label' => 'Payments',
            ],
        ],
    ],
];
