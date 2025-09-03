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
                TextEntry::make('name')
                    ->label(__('entities.fields.name')),
                RepeatableEntry::make('headers')
                    ->label(__('entities.fields.headers'))
                    ->schema([
                        TextEntry::make('content')
                            ->label(__('entities.fields.header'))
                            ->html(),
                    ]),
                RepeatableEntry::make('footers')
                    ->label(__('entities.fields.footers'))
                    ->schema([
                        TextEntry::make('content')
                            ->label(__('entities.fields.footer'))
                            ->html(),
                    ]),
                SpatieMediaLibraryImageEntry::make('stamps')
                    ->label(__('entities.fields.stamps'))
                    ->collection('stamps'),
                SpatieMediaLibraryImageEntry::make('logos')
                    ->label(__('entities.fields.logos'))
                    ->collection('logos'),
                TextEntry::make('created_at')
                    ->label(__('entities.fields.created_at'))
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label(__('entities.fields.updated_at'))
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->label(__('entities.fields.deleted_at'))
                    ->dateTime(),
            ]);
    }
}
