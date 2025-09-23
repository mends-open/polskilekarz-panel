<?php

namespace App\Enums\Chatwoot;

enum MessagePrivacy: string
{
    case Public = 'public';
    case Private = 'private';

    public function toPayload(): bool
    {
        return $this === self::Private;
    }
}
