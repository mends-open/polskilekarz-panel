<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Enums\Appointment\Type;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('patient_id')
                    ->label(__('appointment.fields.patient_id'))
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->label(__('appointment.fields.user_id'))
                    ->required()
                    ->numeric(),
                Select::make('type')
                    ->label(__('appointment.fields.type'))
                    ->options(Type::class)
                    ->required(),
                TextInput::make('duration')
                    ->label(__('appointment.fields.duration'))
                    ->required()
                    ->numeric(),
                DateTimePicker::make('scheduled_at')
                    ->label(__('appointment.fields.scheduled_at'))
                    ->required(),
                DateTimePicker::make('confirmed_at')
                    ->label(__('appointment.fields.confirmed_at')),
                DateTimePicker::make('started_at')
                    ->label(__('appointment.fields.started_at')),
                DateTimePicker::make('cancelled_at')
                    ->label(__('appointment.fields.cancelled_at')),
            ]);
    }
}
