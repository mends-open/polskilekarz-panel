<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('users.fields.name'))
                    ->required(),
                TextInput::make('email')
                    ->label(__('users.fields.email'))
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at')
                    ->label(__('users.fields.email_verified_at')),
                TextInput::make('password')
                    ->label(__('users.fields.password'))
                    ->password()
                    ->required(),
                Repeater::make('signatures')
                    ->label(__('users.fields.signatures'))
                    ->schema([
                        RichEditor::make('content')
                            ->label(__('users.fields.signature')),
                    ]),
                SpatieMediaLibraryFileUpload::make('stamps')
                    ->label(__('users.fields.stamps'))
                    ->collection('stamps')
                    ->multiple(),
            ]);
    }
}
