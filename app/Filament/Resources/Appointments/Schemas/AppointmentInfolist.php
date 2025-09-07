<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Enums\Appointment\Type;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AppointmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('patient_id')
                    ->label(__('appointments.fields.patient_id'))
                    ->numeric(),
                TextEntry::make('user_id')
                    ->label(__('appointments.fields.user_id'))
                    ->numeric(),
                TextEntry::make('type')
                    ->label(__('appointments.fields.type'))
                    ->formatStateUsing(fn (?string $state) => $state ? Type::labels()[$state] ?? $state : null),
                TextEntry::make('duration')
                    ->label(__('appointments.fields.duration'))
                    ->numeric(),
                TextEntry::make('scheduled_at')
                    ->label(__('appointments.fields.scheduled_at'))
                    ->dateTime(),
                TextEntry::make('confirmed_at')
                    ->label(__('appointments.fields.confirmed_at'))
                    ->dateTime(),
                TextEntry::make('started_at')
                    ->label(__('appointments.fields.started_at'))
                    ->dateTime(),
                TextEntry::make('cancelled_at')
                    ->label(__('appointments.fields.cancelled_at'))
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->label(__('appointments.fields.created_at'))
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label(__('appointments.fields.updated_at'))
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->label(__('appointments.fields.deleted_at'))
                    ->dateTime(),
            ]);
    }
}
