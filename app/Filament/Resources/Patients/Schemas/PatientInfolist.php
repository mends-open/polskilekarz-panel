<?php

namespace App\Filament\Resources\Patients\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PatientInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('first_name'),
                TextEntry::make('last_name'),
                TextEntry::make('birth_date')
                    ->date(),
                TextEntry::make('gender'),
                RepeatableEntry::make('addresses')
                    ->label('Addresses')
                    ->schema([
                        TextEntry::make('line1')
                            ->label('Line 1'),
                        TextEntry::make('city'),
                        TextEntry::make('postal_code'),
                        TextEntry::make('country'),
                    ]),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->dateTime(),
            ]);
    }
}
