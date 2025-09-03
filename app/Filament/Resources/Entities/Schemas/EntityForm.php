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
                    ->required(),
                Repeater::make('headers')
                    ->label('Headers')
                    ->schema([
                        RichEditor::make('content')
                            ->label('Header'),
                    ]),
                Repeater::make('footers')
                    ->label('Footers')
                    ->schema([
                        RichEditor::make('content')
                            ->label('Footer'),
                    ]),
                SpatieMediaLibraryFileUpload::make('stamps')
                    ->label('Stamps')
                    ->collection('stamps')
                    ->multiple(),
                SpatieMediaLibraryFileUpload::make('logos')
                    ->label('Logos')
                    ->collection('logos')
                    ->multiple(),
            ]);
    }
}
