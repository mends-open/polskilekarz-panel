<?php

namespace App\Filament\Resources\Patients\Schemas;

use App\Enums\Patient\Gender;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;

class PatientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->label(__('patients.fields.first_name'))
                    ->required(),
                TextInput::make('last_name')
                    ->label(__('patients.fields.last_name'))
                    ->required(),
                DatePicker::make('birth_date')
                    ->label(__('patients.fields.birth_date'))
                    ->required(),
                ToggleButtons::make('gender')
                    ->label(__('patients.fields.gender'))
                    ->options(Gender::class)
                    ->inline()
                    ->required(),
                Repeater::make('addresses')
                    ->label(__('patients.fields.addresses'))
                    ->schema([
                        TextInput::make('line1')
                            ->label(__('patients.fields.line1')),
                        TextInput::make('city')
                            ->label(__('patients.fields.city')),
                        TextInput::make('postal_code')
                            ->label(__('patients.fields.postal_code')),
                        TextInput::make('country')
                            ->label(__('patients.fields.country')),
                    ]),
                TextInput::make('identifiers')
                    ->label(__('patients.fields.identifiers')),
            ]);
    }
}
