<?php

namespace App\Enums\Chatwoot;

enum MessageType: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';
}
