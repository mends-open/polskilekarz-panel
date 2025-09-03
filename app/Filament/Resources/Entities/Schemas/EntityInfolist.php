<?php

namespace App\Filament\Resources\Entities\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class EntityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                RepeatableEntry::make('headers')
                    ->label('Headers')
                    ->schema([
                        TextEntry::make('content')
                            ->html(),
                    ]),
                RepeatableEntry::make('footers')
                    ->label('Footers')
                    ->schema([
                        TextEntry::make('content')
                            ->html(),
                    ]),
                SpatieMediaLibraryImageEntry::make('stamps')
                    ->label('Stamps')
                    ->collection('stamps'),
                SpatieMediaLibraryImageEntry::make('logos')
                    ->label('Logos')
                    ->collection('logos'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->dateTime(),
            ]);
    }
}
