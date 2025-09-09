<?php

namespace App\Filament\Resources\Patients\Schemas;

use App\Enums\Patient\Gender;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PatientInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('first_name')
                    ->label(__('patient.fields.first_name')),
                TextEntry::make('last_name')
                    ->label(__('patient.fields.last_name')),
                TextEntry::make('birth_date')
                    ->label(__('patient.fields.birth_date'))
                    ->date(),
                TextEntry::make('gender')
                    ->label(__('patient.fields.gender'))
                    ->formatStateUsing(fn (?int $state) => $state !== null ? Gender::labels()[$state] ?? (string) $state : null),
                RepeatableEntry::make('addresses')
                    ->label(__('patient.fields.addresses'))
                    ->schema([
                        TextEntry::make('line1')
                            ->label(__('patient.fields.line1')),
                        TextEntry::make('city')
                            ->label(__('patient.fields.city')),
                        TextEntry::make('postal_code')
                            ->label(__('patient.fields.postal_code')),
                        TextEntry::make('country')
                            ->label(__('patient.fields.country')),
                    ]),
                TextEntry::make('created_at')
                    ->label(__('patient.fields.created_at'))
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label(__('patient.fields.updated_at'))
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->label(__('patient.fields.deleted_at'))
                    ->dateTime(),
            ]);
    }
}
