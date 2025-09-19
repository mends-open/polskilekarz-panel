<?php

namespace App\Filament\Resources\Entities\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EntityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('entity.fields.name'))
                    ->required(),
                Repeater::make('headers')
                    ->label(__('entity.fields.headers'))
                    ->schema([
                        RichEditor::make('content')
                            ->label(__('entity.fields.header')),
                    ]),
                Repeater::make('footers')
                    ->label(__('entity.fields.footers'))
                    ->schema([
                        RichEditor::make('content')
                            ->label(__('entity.fields.footer')),
                    ]),
                SpatieMediaLibraryFileUpload::make('stamps')
                    ->label(__('entity.fields.stamps'))
                    ->collection('stamps')
                    ->multiple(),
                SpatieMediaLibraryFileUpload::make('logos')
                    ->label(__('entity.fields.logos'))
                    ->collection('logos')
                    ->multiple(),
            ]);
    }
}
