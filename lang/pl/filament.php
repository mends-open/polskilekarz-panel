<?php

return [
    'widgets' => [
        'common' => [
            'placeholders' => [
                'name' => 'Brak nazwy',
                'created_at' => 'Brak daty utworzenia',
                'email' => 'Brak adresu e-mail',
                'phone' => 'Brak telefonu',
                'country' => 'Brak kraju',
            ],
        ],
        'chatwoot' => [
            'contact_infolist' => [
                'section' => [
                    'title' => 'Kontakt',
                ],
                'actions' => [
                    'sync_customer_from_contact' => [
                        'label' => 'Synchronizuj klienta',
                    ],
                ],
                'fields' => [
                    'id' => [
                        'label' => 'ID',
                        'placeholder' => 'Brak identyfikatora',
                    ],
                    'name' => [
                        'label' => 'Nazwa',
                    ],
                    'created_at' => [
                        'label' => 'Utworzono',
                    ],
                    'email' => [
                        'label' => 'Adres e-mail',
                    ],
                    'phone_number' => [
                        'label' => 'Telefon',
                    ],
                    'country_code' => [
                        'label' => 'Kraj',
                    ],
                ],
            ],
        ],
        'stripe' => [
            'customer_infolist' => [
                'section' => [
                    'title' => 'Klient',
                ],
                'actions' => [
                    'send_portal_link' => [
                        'label' => 'Wyślij link do portalu',
                        'modal' => [
                            'heading' => 'Wyślać link do portalu?',
                            'description' => 'Utworzymy sesję portalu klienta Stripe i wyślemy skrócony link w Chatwoocie.',
                        ],
                    ],
                    'open_portal' => [
                        'label' => 'Otwórz portal',
                    ],
                ],
                'fields' => [
                    'id' => [
                        'label' => 'ID',
                    ],
                    'name' => [
                        'label' => 'Nazwa',
                    ],
                    'created' => [
                        'label' => 'Utworzono',
                    ],
                    'email' => [
                        'label' => 'Adres e-mail',
                    ],
                    'phone' => [
                        'label' => 'Telefon',
                    ],
                    'address_country' => [
                        'label' => 'Kraj',
                    ],
                ],
            ],
            'latest_invoice_infolist' => [
                'section' => [
                    'title' => 'Najnowsza faktura',
                ],
                'actions' => [
                    'create_invoice' => [
                        'label' => 'Utwórz nową',
                        'modal' => [
                            'heading' => 'Utwórz fakturę',
                        ],
                    ],
                    'send_latest' => [
                        'label' => 'Wyślij najnowszą',
                    ],
                    'open_latest' => [
                        'label' => 'Otwórz najnowszą',
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
                        'label' => 'Utworzono',
                    ],
                    'due_date' => [
                        'label' => 'Termin płatności',
                    ],
                    'total' => [
                        'label' => 'Suma',
                    ],
                    'amount_paid' => [
                        'label' => 'Zapłacono',
                    ],
                    'amount_remaining' => [
                        'label' => 'Pozostało do zapłaty',
                    ],
                    'collection_method' => [
                        'label' => 'Metoda pobrania płatności',
                    ],
                ],
            ],
            'invoices_table' => [
                'heading' => 'Faktury',
                'columns' => [
                    'id' => [
                        'label' => 'ID',
                    ],
                    'number' => [
                        'label' => 'Numer',
                    ],
                    'total' => [
                        'label' => 'Suma',
                    ],
                    'status' => [
                        'label' => 'Status',
                    ],
                    'currency' => [
                        'label' => 'Waluta',
                    ],
                    'created' => [
                        'label' => 'Utworzono',
                    ],
                    'lines' => [
                        'description' => [
                            'label' => 'Opis',
                        ],
                        'quantity' => [
                            'label' => 'Ilość',
                            'prefix' => 'x',
                        ],
                        'amount' => [
                            'label' => 'Suma częściowa',
                        ],
                    ],
                ],
                'actions' => [
                    'send_latest' => [
                        'modal' => [
                            'heading' => 'Wysłać link do najnowszej faktury?',
                            'description' => 'Wyślemy link do najnowszej faktury w aktywnej konwersacji Chatwoot.',
                        ],
                    ],
                    'duplicate' => [
                        'label' => 'Duplikuj',
                    ],
                    'send' => [
                        'label' => 'Wyślij',
                        'modal' => [
                            'heading' => 'Wysłać link do faktury?',
                            'description' => 'Wyślemy ten link do faktury do bieżącej konwersacji Chatwoot.',
                        ],
                    ],
                    'open' => [
                        'label' => 'Otwórz',
                    ],
                ],
            ],
            'latest_invoice_lines_table' => [
                'heading' => 'Pozycje najnowszej faktury',
                'columns' => [
                    'price' => [
                        'label' => 'ID ceny',
                    ],
                    'product' => [
                        'label' => 'ID produktu',
                    ],
                    'description' => [
                        'label' => 'Opis',
                    ],
                    'unit_amount' => [
                        'label' => 'Cena jednostkowa',
                    ],
                    'quantity' => [
                        'label' => 'Ilość',
                        'prefix' => 'x',
                    ],
                    'amount' => [
                        'label' => 'Suma częściowa',
                    ],
                ],
                'actions' => [
                    'duplicate' => [
                        'label' => 'Duplikuj',
                        'modal' => [
                            'heading' => 'Zduplikować najnowszą fakturę?',
                        ],
                    ],
                ],
            ],
            'payments_table' => [
                'heading' => 'Płatności',
                'columns' => [
                    'id' => [
                        'label' => 'ID',
                    ],
                    'amount' => [
                        'label' => 'Kwota',
                    ],
                    'status' => [
                        'label' => 'Status',
                    ],
                    'payment_method_type' => [
                        'label' => 'Metoda płatności',
                    ],
                    'created' => [
                        'label' => 'Utworzono',
                    ],
                ],
                'actions' => [
                    'open_receipt' => [
                        'label' => 'Otwórz paragon',
                    ],
                ],
            ],
            'invoice_form' => [
                'repeater' => [
                    'label' => 'Produkty',
                    'validation_attribute' => 'produkty',
                ],
                'table_columns' => [
                    'product' => 'Produkt',
                    'price' => 'Cena',
                    'quantity' => 'Ilość',
                    'subtotal' => 'Suma częściowa',
                ],
                'fields' => [
                    'product' => [
                        'label' => 'Produkt',
                        'placeholder' => 'Wybierz produkt',
                    ],
                    'price' => [
                        'label' => 'Cena',
                        'placeholder' => 'Wybierz cenę',
                    ],
                    'quantity' => [
                        'label' => 'Ilość',
                    ],
                    'subtotal' => [
                        'label' => 'Suma częściowa',
                    ],
                ],
                'defaults' => [
                    'product_name' => 'Produkt',
                ],
                'actions' => [
                    'submit' => 'Utwórz fakturę',
                ],
            ],
        ],
    ],
    'pages' => [
        'payments' => [
            'title' => 'Płatności',
            'navigation' => [
                'label' => 'Płatności',
            ],
        ],
    ],
];
