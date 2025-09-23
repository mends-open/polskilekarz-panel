<?php

namespace App\Enums\Chatwoot;

enum ContentType: string
{
    case Text = 'text';
    case InputSelect = 'input_select';
    case Cards = 'cards';
    case Articles = 'articles';
    case InputEmail = 'input_email';
    case InputPhone = 'input_phone';
    case Forms = 'forms';
}
