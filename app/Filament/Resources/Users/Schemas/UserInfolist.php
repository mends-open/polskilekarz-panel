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
                TextEntry::make('name')
                    ->label(__('users.fields.name')),
                TextEntry::make('email')
                    ->label(__('users.fields.email')),
                RepeatableEntry::make('signatures')
                    ->label(__('users.fields.signatures'))
                    ->schema([
                        TextEntry::make('content')
                            ->label(__('users.fields.signature'))
                            ->html(),
                    ]),
                SpatieMediaLibraryImageEntry::make('stamps')
                    ->label(__('users.fields.stamps'))
                    ->collection('stamps'),
                TextEntry::make('email_verified_at')
                    ->label(__('users.fields.email_verified_at'))
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->label(__('users.fields.created_at'))
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label(__('users.fields.updated_at'))
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->label(__('users.fields.deleted_at'))
                    ->dateTime(),
            ]);
    }
}
