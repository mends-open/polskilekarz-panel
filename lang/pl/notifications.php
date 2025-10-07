<?php

return [
    'chatwoot' => [
        'contact_infolist' => [
            'missing_context' => [
                'title' => 'Brak kontekstu Chatwoot',
                'body' => 'Nie znaleźliśmy szczegółów kontaktu Chatwoot. Otwórz ten widżet z poziomu konwersacji w Chatwoocie.',
            ],
            'load_failed' => [
                'title' => 'Nie udało się wczytać kontaktu Chatwoot',
                'body' => 'Nie udało się pobrać danych kontaktu z Chatwoot. Spróbuj ponownie.',
            ],
            'create_customer_failed' => [
                'title' => 'Nie udało się utworzyć klienta Stripe',
                'body' => 'Nie udało się utworzyć klienta Stripe na podstawie kontaktu z Chatwoot. Spróbuj ponownie.',
            ],
            'customer_created' => [
                'title' => 'Utworzono klienta Stripe',
                'body' => 'Klient Stripe został utworzony na podstawie danych kontaktu z Chatwoot.',
            ],
            'nothing_to_sync' => [
                'title' => 'Brak danych do synchronizacji',
                'body' => 'Kontakt w Chatwoot nie ma danych, które można skopiować do klienta Stripe.',
            ],
            'syncing' => [
                'title' => 'Synchronizujemy dane klienta',
                'body' => 'Pobieramy dane kontaktu z Chatwoot i aktualizujemy klienta Stripe.',
            ],
        ],
    ],
    'stripe' => [
        'customer_infolist' => [
            'missing_customer' => [
                'title' => 'Brak klienta Stripe',
                'body' => 'Nie znaleźliśmy klienta Stripe. Wybierz najpierw klienta.',
            ],
            'missing_chatwoot_context' => [
                'title' => 'Brak kontekstu Chatwoot',
                'body' => 'Aby wysłać link do portalu potrzebna jest konwersacja Chatwoot. Otwórz ten widżet z poziomu konwersacji.',
            ],
            'generating_portal_link' => [
                'title' => 'Generujemy link do portalu',
                'body' => 'Tworzymy sesję portalu klienta Stripe i wkrótce wyślemy link.',
            ],
            'open_portal_failed' => [
                'title' => 'Nie udało się otworzyć portalu',
                'body' => 'Nie udało się otworzyć portalu klienta. Spróbuj ponownie.',
            ],
        ],
        'interacts_with_invoices' => [
            'invoice_link_unavailable' => [
                'title' => 'Brak linku do faktury',
                'body' => 'Nie znaleźliśmy adresu URL hostowanej faktury.',
            ],
            'missing_chatwoot_context' => [
                'title' => 'Brak kontekstu Chatwoot',
                'body' => 'Nie można wysłać linku do faktury, ponieważ kontekst Chatwoot jest niekompletny.',
            ],
            'sending_invoice_link' => [
                'title' => 'Wysyłamy link do faktury',
                'body' => 'Przygotowujemy link do faktury i wkrótce wyślemy go do konwersacji.',
            ],
        ],
        'invoice_form' => [
            'no_products' => [
                'title' => 'Nie wybrano produktów',
                'body' => 'Wybierz co najmniej jeden produkt i cenę, aby dodać je do faktury.',
            ],
            'mixed_currencies' => [
                'title' => 'Wybrano różne waluty',
                'body' => 'Wszystkie wybrane produkty muszą mieć tę samą walutę. Dostosuj wybór i spróbuj ponownie.',
            ],
            'creating_invoice' => [
                'title' => 'Tworzymy fakturę',
                'body' => 'Przygotowujemy fakturę w Stripe. Otrzymasz powiadomienie, gdy będzie gotowa.',
            ],
            'missing_chatwoot_context' => [
                'title' => 'Brak kontekstu Chatwoot',
                'body' => 'Do utworzenia klienta Stripe potrzebny jest kontakt z Chatwoot. Otwórz ten widżet z poziomu konwersacji.',
            ],
        ],
    ],
    'jobs' => [
        'stripe' => [
            'create_customer_portal_session_link' => [
                'success' => [
                    'title' => 'Wygenerowano link do portalu klienta',
                    'body' => 'Link do portalu klienta został wygenerowany i wkrótce zostanie wysłany.',
                ],
                'failed' => [
                    'title' => 'Nie udało się utworzyć linku do portalu',
                    'body' => 'Nie udało się wygenerować linku do portalu klienta. Spróbuj ponownie.',
                ],
            ],
            'sync_customer_from_chatwoot_contact' => [
                'updated' => [
                    'title' => 'Zaktualizowano klienta Stripe',
                    'body' => 'Klient Stripe został zaktualizowany na podstawie danych kontaktu z Chatwoot.',
                ],
                'failed' => [
                    'title' => 'Nie udało się zaktualizować klienta Stripe',
                    'body' => 'Nie udało się zsynchronizować klienta Stripe z kontaktem z Chatwoot. Spróbuj ponownie.',
                ],
            ],
            'create_invoice' => [
                'success' => [
                    'title' => 'Faktura utworzona',
                    'body' => 'Faktura :invoice została utworzona.',
                    'with_total' => [
                        'body' => 'Faktura :invoice na kwotę :amount została utworzona.',
                    ],
                ],
                'failed' => [
                    'title' => 'Nie udało się utworzyć faktury',
                    'body' => 'Nie udało się utworzyć faktury w Stripe. Spróbuj ponownie.',
                ],
            ],
        ],
        'chatwoot' => [
            'create_invoice_short_link' => [
                'success' => [
                    'title' => 'Skrócono link do faktury',
                    'body' => 'Link do faktury został skrócony i wkrótce zostanie dostarczony.',
                ],
                'failed' => [
                    'title' => 'Nie udało się skrócić linku do faktury',
                    'body' => 'Nie udało się utworzyć krótkiego linku do faktury. Spróbuj ponownie.',
                ],
            ],
            'send_invoice_short_link_message' => [
                'success' => [
                    'title' => 'Wysłano link do faktury',
                    'body' => 'Skrócony link do faktury został wysłany do konwersacji w Chatwoot.',
                ],
                'failed' => [
                    'title' => 'Nie udało się wysłać linku do faktury',
                    'body' => 'Nie udało się wysłać linku do faktury do konwersacji w Chatwoot. Spróbuj ponownie.',
                ],
            ],
            'send_customer_portal_link_message' => [
                'success' => [
                    'title' => 'Wysłano link do portalu klienta',
                    'body' => 'Link do portalu klienta został wysłany do konwersacji w Chatwoot.',
                ],
                'failed' => [
                    'title' => 'Nie udało się wysłać linku do portalu klienta',
                    'body' => 'Nie udało się wysłać linku do portalu klienta do konwersacji w Chatwoot. Spróbuj ponownie.',
                ],
            ],
        ],
    ],
];
