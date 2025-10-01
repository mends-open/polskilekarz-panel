<?php

namespace App\Filament\Widgets;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;

class BaseSchemaWidget extends Widget implements HasActions, HasInfolists, HasSchemas
{
    use InteractsWithActions, InteractsWithInfolists, InteractsWithSchemas;

    protected string $view = 'filament.widgets.base-schema-widget';
}
