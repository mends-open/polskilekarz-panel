<?php

namespace App\Filament\Resources\Appointments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('patient_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('type')
                    ->required(),
                TextInput::make('duration')
                    ->required()
                    ->numeric(),
                DateTimePicker::make('scheduled_at')
                    ->required(),
                DateTimePicker::make('confirmed_at'),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('cancelled_at'),
            ]);
    }
}
