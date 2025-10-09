<?php

return [
    'widgets' => [
        'common' => [
            'placeholders' => [
                'blank' => ' ',
                'id' => 'Brak identyfikatora',
                'name' => 'Brak nazwy',
                'created_at' => 'Brak daty utworzenia',
                'email' => 'Brak adresu e-mail',
                'phone' => 'Brak telefonu',
                'country' => 'Brak kraju',
                'status' => 'Brak statusu',
                'due_date' => 'Brak terminu płatności',
                'total' => 'Brak sumy',
                'amount_paid' => 'Brak kwoty zapłaconej',
                'amount_remaining' => 'Brak kwoty do zapłaty',
                'currency' => 'Brak waluty',
                'number' => 'Brak numeru',
                'description' => 'Brak opisu',
                'unit_price' => 'Brak ceny jednostkowej',
                'quantity' => 'Brak ilości',
                'subtotal' => 'Brak sumy częściowej',
                'payment_method' => 'Brak metody płatności',
                'price' => 'Brak ceny',
                'product' => 'Brak produktu',
                'metadata' => 'Brak metadanych',
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
        'cloudflare' => [
            'links_table' => [
                'heading' => 'Aktywność skróconych linków',
                'empty_state' => [
                    'heading' => 'Brak aktywności linków',
                    'description' => 'Nie zarejestrowaliśmy jeszcze odwiedzin skróconych linków dla tego kontaktu.',
                ],
                'columns' => [
                    'slug' => [
                        'label' => 'Slug',
                    ],
                    'short_url' => [
                        'label' => 'Skrócony link',
                    ],
                    'url' => [
                        'label' => 'Docelowy adres',
                    ],
                    'entity_type' => [
                        'label' => 'Typ obiektu',
                    ],
                    'metadata' => [
                        'label' => 'Metadane',
                    ],
                    'request_method' => [
                        'label' => 'Metoda',
                    ],
                    'request_url' => [
                        'label' => 'Adres żądania',
                    ],
                    'request_country' => [
                        'label' => 'Kraj',
                    ],
                    'request_city' => [
                        'label' => 'Miasto',
                    ],
                    'request_ip' => [
                        'label' => 'Adres IP',
                    ],
                    'response_status' => [
                        'label' => 'Status',
                    ],
                    'timestamp' => [
                        'label' => 'Odwiedzono',
                    ],
                    'timestamp_exact' => [
                        'label' => 'Data odwiedzin',
                    ],
                ],
                'enums' => [
                    'entity_types' => [
                        'invoice' => 'Faktura',
                        'billing_portal' => 'Portal rozliczeń',
                        'customer' => 'Klient',
                        'link' => 'Link',
                    ],
                ],
                'metadata_keys' => [
                    'chatwoot_account_id' => 'Konto Chatwoot',
                    'chatwoot_conversation_id' => 'Konwersacja Chatwoot',
                    'chatwoot_inbox_id' => 'Skrzynka Chatwoot',
                    'chatwoot_contact_id' => 'Kontakt Chatwoot',
                    'chatwoot_user_id' => 'Użytkownik Chatwoot',
                    'user_id' => 'Użytkownik',
                    'stripe_customer_id' => 'Klient Stripe',
                    'stripe_invoice_id' => 'Faktura Stripe',
                    'stripe_billing_portal_session' => 'Sesja portalu rozliczeń',
                ],
            ],
        ],
        'stripe' => [
            'enums' => [
                'invoice_statuses' => [
                    'draft' => 'Szkic',
                    'open' => 'Otwarte',
                    'paid' => 'Opłacone',
                    'uncollectible' => 'Nieściągalne',
                    'void' => 'Unieważnione',
                ],
                'payment_intent_statuses' => [
                    'canceled' => 'Anulowano',
                    'processing' => 'Przetwarzanie',
                    'requires_action' => 'Wymaga działania',
                    'requires_capture' => 'Wymaga przechwycenia',
                    'requires_confirmation' => 'Wymaga potwierdzenia',
                    'requires_payment_method' => 'Wymaga metody płatności',
                    'succeeded' => 'Zakończono pomyślnie',
                ],
            ],
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
                    'title' => 'Ostatnia faktura',
                ],
                'actions' => [
                    'create_invoice' => [
                        'label' => 'Utwórz nową',
                        'modal' => [
                            'heading' => 'Utwórz fakturę',
                        ],
                    ],
                    'send_latest' => [
                        'label' => 'Wyślij',
                        'modal' => [
                            'heading' => 'Wysłać link do ostatniej faktury?',
                            'description' => 'Wyślemy link do ostatniej faktury w aktywnej konwersacji Chatwoot.',
                        ],
                    ],
                    'open_latest' => [
                        'label' => 'Otwórz',
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
                        'label' => 'Do zapłaty',
                    ],
                    'currency' => [
                        'label' => 'Waluta',
                    ],
                ],
            ],
            'invoices_table' => [
                'heading' => 'Faktury',
                'empty_state' => [
                    'heading' => 'Brak faktur',
                    'description' => 'Ten klient nie ma jeszcze żadnych faktur.',
                ],
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
                            'heading' => 'Wysłać link do ostatniej faktury?',
                            'description' => 'Wyślemy link do ostatniej faktury w aktywnej konwersacji Chatwoot.',
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
                'heading' => 'Pozycje ostatniej faktury',
                'empty_state' => [
                    'heading' => 'Brak pozycji',
                    'description' => 'Ostatnia faktura nie zawiera żadnych pozycji.',
                ],
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
                            'heading' => 'Zduplikować ostatnią fakturę?',
                        ],
                    ],
                ],
            ],
            'payments_table' => [
                'heading' => 'Płatności',
                'empty_state' => [
                    'heading' => 'Brak płatności',
                    'description' => 'Ten klient nie ma jeszcze żadnych płatności.',
                ],
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
