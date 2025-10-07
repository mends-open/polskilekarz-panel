<?php

return [
    'actions' => [
        'send' => 'Wyślij',
        'open' => 'Otwórz',
    ],
    'invoices_table' => [
        'heading' => 'Faktury',
        'actions' => [
            'send_latest' => [
                'modal' => [
                    'heading' => 'Wysłać link do najnowszej faktury?',
                    'description' => 'Wyślemy link do najnowszej faktury do aktywnej rozmowy w Chatwoot.',
                ],
            ],
            'duplicate' => [
                'label' => 'Duplikuj',
            ],
            'send' => [
                'label' => 'Wyślij',
                'modal' => [
                    'heading' => 'Wysłać link do faktury?',
                    'description' => 'Wyślemy ten link do faktury do bieżącej rozmowy w Chatwoot.',
                ],
            ],
            'open' => [
                'label' => 'Otwórz',
            ],
        ],
    ],
    'customer_infolist' => [
        'heading' => 'Klient',
        'actions' => [
            'send_portal_link' => [
                'label' => 'Wyślij link do portalu',
                'modal' => [
                    'heading' => 'Wysłać link do portalu?',
                    'description' => 'Utworzymy sesję portalu klienta Stripe i wyślemy skrócony link w Chatwoot.',
                ],
            ],
            'open_portal' => 'Otwórz portal',
        ],
        'placeholders' => [
            'name' => 'Brak nazwy',
            'created' => 'Brak daty utworzenia',
            'email' => 'Brak adresu e-mail',
            'phone' => 'Brak telefonu',
            'country' => 'Brak kraju',
        ],
        'labels' => [
            'country' => 'Kraj',
        ],
        'notifications' => [
            'missing_customer' => [
                'title' => 'Brak klienta Stripe',
                'body' => 'Nie mogliśmy znaleźć klienta Stripe. Najpierw wybierz klienta.',
            ],
            'missing_context' => [
                'title' => 'Brak kontekstu Chatwoot',
                'body' => 'Potrzebujemy rozmowy w Chatwoot, aby wysłać link do portalu. Otwórz ten widżet z rozmowy w Chatwoot.',
            ],
            'generating_portal' => [
                'title' => 'Generowanie linku do portalu',
                'body' => 'Generujemy sesję portalu klienta Stripe i wkrótce wyślemy link.',
            ],
            'open_portal_failed' => [
                'title' => 'Nie udało się otworzyć portalu',
                'body' => 'Nie udało się otworzyć portalu klienta. Spróbuj ponownie.',
            ],
        ],
    ],
    'latest_invoice_infolist' => [
        'heading' => 'Najnowsza faktura',
        'actions' => [
            'create_new' => 'Utwórz nową',
            'send_latest' => 'Wyślij najnowszą',
            'open_latest' => 'Otwórz najnowszą',
        ],
        'fields' => [
            'total' => 'Razem',
            'amount_paid' => 'Kwota zapłacona',
            'amount_remaining' => 'Pozostało do zapłaty',
        ],
        'modals' => [
            'create_invoice' => 'Utwórz fakturę',
        ],
    ],
    'latest_invoice_lines_table' => [
        'heading' => 'Pozycje najnowszej faktury',
        'columns' => [
            'description' => 'Opis',
            'unit_price' => 'Cena jednostkowa',
            'quantity' => 'Ilość',
            'subtotal' => 'Suma częściowa',
        ],
        'actions' => [
            'duplicate' => 'Duplikuj',
        ],
        'modals' => [
            'duplicate_latest' => 'Zduplikuj najnowszą fakturę',
        ],
    ],
    'payments_table' => [
        'heading' => 'Płatności',
        'actions' => [
            'open_receipt' => 'Otwórz paragon',
        ],
    ],
    'invoice_form' => [
        'submit' => 'Utwórz fakturę',
        'notifications' => [
            'no_products' => [
                'title' => 'Nie wybrano produktów',
                'body' => 'Wybierz co najmniej jeden produkt i cenę, aby dodać je do faktury.',
            ],
            'mixed_currencies' => [
                'title' => 'Wybrano różne waluty',
                'body' => 'Wszystkie wybrane produkty muszą używać tej samej waluty. Dostosuj wybór i spróbuj ponownie.',
            ],
            'creating_invoice' => [
                'title' => 'Tworzenie faktury',
                'body' => 'Przygotowujemy fakturę w Stripe. Powiadomimy Cię, gdy będzie gotowa.',
            ],
        ],
    ],
    'notifications' => [
        'create_customer' => [
            'missing_context' => [
                'title' => 'Brak kontekstu Chatwoot',
                'body' => 'Potrzebujemy kontaktu Chatwoot, aby utworzyć klienta Stripe. Otwórz ten widżet z rozmowy w Chatwoot.',
            ],
            'load_contact_failed' => [
                'title' => 'Nie udało się wczytać kontaktu Chatwoot',
                'body' => 'Nie udało się wczytać danych kontaktu Chatwoot. Spróbuj ponownie.',
            ],
            'failed' => [
                'title' => 'Nie udało się utworzyć klienta Stripe',
                'body' => 'Nie udało się utworzyć klienta Stripe na podstawie kontaktu Chatwoot. Spróbuj ponownie.',
            ],
            'success' => [
                'title' => 'Utworzono klienta Stripe',
                'body' => 'Klient Stripe został utworzony na podstawie kontaktu Chatwoot.',
            ],
        ],
        'invoice_link_unavailable' => [
            'title' => 'Link do faktury niedostępny',
            'body' => 'Nie znaleziono hostowanego adresu URL faktury.',
        ],
        'missing_chatwoot_context' => [
            'title' => 'Brak kontekstu Chatwoot',
            'body' => 'Nie można wysłać linku do faktury, ponieważ kontekst Chatwoot jest niepełny.',
        ],
        'sending_invoice_link' => [
            'title' => 'Wysyłanie linku do faktury',
            'body' => 'Przygotowujemy link do faktury i wkrótce wyślemy go do rozmowy.',
        ],
    ],
];
