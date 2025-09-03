<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('email')
                    ->label('Email address'),
                RepeatableEntry::make('signatures')
                    ->label('Signatures')
                    ->schema([
                        TextEntry::make('content')
                            ->html(),
                    ]),
                SpatieMediaLibraryImageEntry::make('stamps')
                    ->label('Stamps')
                    ->collection('stamps')
                    ->multiple(),
                TextEntry::make('email_verified_at')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->dateTime(),
            ]);
    }
}
