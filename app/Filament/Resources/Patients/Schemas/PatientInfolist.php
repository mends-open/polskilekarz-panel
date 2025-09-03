<?php

namespace App\Filament\Resources\Patients\Schemas;

use App\Enums\Patient\PatientGender;
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
                    ->label(__('patients.fields.first_name')),
                TextEntry::make('last_name')
                    ->label(__('patients.fields.last_name')),
                TextEntry::make('birth_date')
                    ->label(__('patients.fields.birth_date'))
                    ->date(),
                TextEntry::make('gender')
                    ->label(__('patients.fields.gender'))
                    ->enum(PatientGender::class),
                RepeatableEntry::make('addresses')
                    ->label(__('patients.fields.addresses'))
                    ->schema([
                        TextEntry::make('line1')
                            ->label(__('patients.fields.line1')),
                        TextEntry::make('city')
                            ->label(__('patients.fields.city')),
                        TextEntry::make('postal_code')
                            ->label(__('patients.fields.postal_code')),
                        TextEntry::make('country')
                            ->label(__('patients.fields.country')),
                    ]),
                TextEntry::make('created_at')
                    ->label(__('patients.fields.created_at'))
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label(__('patients.fields.updated_at'))
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->label(__('patients.fields.deleted_at'))
                    ->dateTime(),
            ]);
    }
}
