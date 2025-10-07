<?php

return [
    'contact_infolist' => [
        'heading' => 'Kontakt',
        'actions' => [
            'sync_customer' => 'Synchronizuj klienta',
        ],
        'placeholders' => [
            'name' => 'Brak nazwy',
            'created_at' => 'Brak daty utworzenia',
            'email' => 'Brak adresu e-mail',
            'phone_number' => 'Brak telefonu',
            'country_code' => 'Brak kodu kraju',
        ],
        'notifications' => [
            'missing_context' => [
                'title' => 'Brak kontekstu Chatwoot',
                'body' => 'Nie mogliśmy znaleźć danych kontaktu Chatwoot. Otwórz ten widżet z rozmowy w Chatwoot.',
            ],
            'no_contact_details' => [
                'title' => 'Brak danych kontaktowych do synchronizacji',
                'body' => 'Kontakt Chatwoot nie zawiera żadnych danych do skopiowania do klienta Stripe.',
            ],
            'syncing_customer' => [
                'title' => 'Synchronizujemy dane klienta',
                'body' => 'Pobieramy dane kontaktu Chatwoot i aktualizujemy klienta Stripe.',
            ],
        ],
    ],
];
