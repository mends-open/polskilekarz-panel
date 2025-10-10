<?php

namespace App\Filament\Widgets\Cloudflare\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CloudflareResponseStatus: int implements HasColor, HasLabel
{
    case Ok = 200;
    case Created = 201;
    case Accepted = 202;
    case NoContent = 204;
    case MovedPermanently = 301;
    case Found = 302;
    case TemporaryRedirect = 307;
    case PermanentRedirect = 308;
    case BadRequest = 400;
    case Unauthorized = 401;
    case Forbidden = 403;
    case NotFound = 404;
    case InternalServerError = 500;
    case BadGateway = 502;
    case ServiceUnavailable = 503;
    case GatewayTimeout = 504;

    public function getLabel(): ?string
    {
        $key = 'filament.widgets.cloudflare.enums.response_statuses.' . $this->value;

        $label = __($key);

        if ($label === $key) {
            return (string) $this->value;
        }

        return $label;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Ok,
            self::Created,
            self::Accepted,
            self::NoContent => 'success',

            self::MovedPermanently,
            self::Found,
            self::TemporaryRedirect,
            self::PermanentRedirect => 'info',

            self::BadRequest,
            self::Unauthorized,
            self::Forbidden,
            self::NotFound => 'warning',

            self::InternalServerError,
            self::BadGateway,
            self::ServiceUnavailable,
            self::GatewayTimeout => 'danger',
        };
    }
}
