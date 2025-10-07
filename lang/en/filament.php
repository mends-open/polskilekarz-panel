<?php

return [
    'widgets' => [
        'common' => [
            'placeholders' => [
                'name' => 'No name',
                'created_at' => 'No creation date',
                'email' => 'No email',
                'phone' => 'No phone',
                'country' => 'No country',
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
                ],
            ],
        ],
        'stripe' => [
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
                    ],
                    'open_latest' => [
                        'label' => 'Open Latest',
                    ],
                ],
                'fields' => [
                    'total' => [
                        'label' => 'Total',
                    ],
                    'amount_paid' => [
                        'label' => 'Amount Paid',
                    ],
                    'amount_remaining' => [
                        'label' => 'Amount Remaining',
                    ],
                ],
            ],
            'invoices_table' => [
                'heading' => 'Invoices',
                'columns' => [
                    'lines' => [
                        'quantity_prefix' => 'x',
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
                'columns' => [
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
];
