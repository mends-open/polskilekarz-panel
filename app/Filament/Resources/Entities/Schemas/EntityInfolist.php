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
                    ->label(__('entity.fields.name')),
                RepeatableEntry::make('headers')
                    ->label(__('entity.fields.headers'))
                    ->schema([
                        TextEntry::make('content')
                            ->label(__('entity.fields.header'))
                            ->html(),
                    ]),
                RepeatableEntry::make('footers')
                    ->label(__('entity.fields.footers'))
                    ->schema([
                        TextEntry::make('content')
                            ->label(__('entity.fields.footer'))
                            ->html(),
                    ]),
                SpatieMediaLibraryImageEntry::make('stamps')
                    ->label(__('entity.fields.stamps'))
                    ->collection('stamps'),
                SpatieMediaLibraryImageEntry::make('logos')
                    ->label(__('entity.fields.logos'))
                    ->collection('logos'),
                TextEntry::make('created_at')
                    ->label(__('entity.fields.created_at'))
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label(__('entity.fields.updated_at'))
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->label(__('entity.fields.deleted_at'))
                    ->dateTime(),
            ]);
    }
}
