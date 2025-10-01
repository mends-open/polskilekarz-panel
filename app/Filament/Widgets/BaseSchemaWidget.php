<?php

namespace App\Filament\Widgets;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;

class BaseSchemaWidget extends Widget implements HasSchemas, HasActions, HasInfolists
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithInfolists;

    protected string $view = 'filament.widgets.base-schema-widget';
}
