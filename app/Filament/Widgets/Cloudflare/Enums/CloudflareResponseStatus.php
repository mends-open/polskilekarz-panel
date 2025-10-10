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
        return match ($this) {
            self::Ok => '200 OK',
            self::Created => '201 Created',
            self::Accepted => '202 Accepted',
            self::NoContent => '204 No Content',
            self::MovedPermanently => '301 Moved Permanently',
            self::Found => '302 Found',
            self::TemporaryRedirect => '307 Temporary Redirect',
            self::PermanentRedirect => '308 Permanent Redirect',
            self::BadRequest => '400 Bad Request',
            self::Unauthorized => '401 Unauthorized',
            self::Forbidden => '403 Forbidden',
            self::NotFound => '404 Not Found',
            self::InternalServerError => '500 Internal Server Error',
            self::BadGateway => '502 Bad Gateway',
            self::ServiceUnavailable => '503 Service Unavailable',
            self::GatewayTimeout => '504 Gateway Timeout',
        };
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
