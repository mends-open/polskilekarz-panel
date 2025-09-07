<?php

namespace App\Filament\Resources\Documents\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('patient_id')
                    ->label(__('document.fields.patient_id'))
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->label(__('document.fields.user_id'))
                    ->required()
                    ->numeric(),
            ]);
    }
}
