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
                    ->label(__('entities.fields.name'))
                    ->required(),
                Repeater::make('headers')
                    ->label(__('entities.fields.headers'))
                    ->schema([
                        RichEditor::make('content')
                            ->label(__('entities.fields.header')),
                    ]),
                Repeater::make('footers')
                    ->label(__('entities.fields.footers'))
                    ->schema([
                        RichEditor::make('content')
                            ->label(__('entities.fields.footer')),
                    ]),
                SpatieMediaLibraryFileUpload::make('stamps')
                    ->label(__('entities.fields.stamps'))
                    ->collection('stamps')
                    ->multiple(),
                SpatieMediaLibraryFileUpload::make('logos')
                    ->label(__('entities.fields.logos'))
                    ->collection('logos')
                    ->multiple(),
            ]);
    }
}
