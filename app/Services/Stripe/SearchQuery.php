<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use BadMethodCallException;
use DateTimeInterface;
use InvalidArgumentException;
use Stringable;

enum StripeSearchFieldType: string
{
    case String = 'string';
    case Numeric = 'numeric';
    case Timestamp = 'timestamp';
    case Boolean = 'boolean';
    case Unknown = 'unknown';
}
