<?php

namespace App\Filament\Resources\Entities\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EntityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('headers'),
                TextInput::make('footers'),
                TextInput::make('stamps'),
                TextInput::make('logos'),
            ]);
    }
}
