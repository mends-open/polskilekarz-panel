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
                    ->label(__('user.fields.name')),
                TextEntry::make('email')
                    ->label(__('user.fields.email')),
                RepeatableEntry::make('signatures')
                    ->label(__('user.fields.signatures'))
                    ->schema([
                        TextEntry::make('content')
                            ->label(__('user.fields.signature'))
                            ->html(),
                    ]),
                SpatieMediaLibraryImageEntry::make('stamps')
                    ->label(__('user.fields.stamps'))
                    ->collection('stamps'),
                TextEntry::make('email_verified_at')
                    ->label(__('user.fields.email_verified_at'))
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->label(__('user.fields.created_at'))
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label(__('user.fields.updated_at'))
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->label(__('user.fields.deleted_at'))
                    ->dateTime(),
            ]);
    }
}
