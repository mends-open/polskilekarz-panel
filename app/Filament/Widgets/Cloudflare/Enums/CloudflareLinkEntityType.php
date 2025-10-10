<?php

namespace App\Filament\Widgets\Cloudflare\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum CloudflareLinkEntityType: string implements HasColor, HasLabel
{
    case Invoice = 'invoice';
    case BillingPortal = 'billing_portal';
    case Customer = 'customer';
    case Link = 'link';

    public function getLabel(): ?string
    {
        $key = 'filament.widgets.cloudflare.enums.entity_types.' . $this->value;

        $label = __($key);

        if ($label === $key) {
            return Str::headline($this->value);
        }

        return $label;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Invoice => 'info',
            self::BillingPortal => 'warning',
            self::Customer => 'success',
            self::Link => 'gray',
        };
    }
}

