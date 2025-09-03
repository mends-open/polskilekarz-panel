<?php

namespace App\Filament\Resources\Patients\Schemas;

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
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
                DatePicker::make('birth_date')
                    ->required(),
                ToggleButtons::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                        'other' => 'Other',
                    ])
                    ->inline()
                    ->required(),
                Repeater::make('addresses')
                    ->label('Addresses')
                    ->schema([
                        TextInput::make('line1')
                            ->label('Line 1'),
                        TextInput::make('city'),
                        TextInput::make('postal_code'),
                        TextInput::make('country'),
                    ]),
                TextInput::make('identifiers'),
            ]);
    }
}
