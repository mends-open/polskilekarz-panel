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
                    ->label(__('appointment.fields.patient_id'))
                    ->numeric(),
                TextEntry::make('user_id')
                    ->label(__('appointment.fields.user_id'))
                    ->numeric(),
                TextEntry::make('type')
                    ->label(__('appointment.fields.type'))
                    ->formatStateUsing(fn (?string $state) => $state ? Type::labels()[$state] ?? $state : null),
                TextEntry::make('duration')
                    ->label(__('appointment.fields.duration'))
                    ->numeric(),
                TextEntry::make('scheduled_at')
                    ->label(__('appointment.fields.scheduled_at'))
                    ->dateTime(),
                TextEntry::make('confirmed_at')
                    ->label(__('appointment.fields.confirmed_at'))
                    ->dateTime(),
                TextEntry::make('started_at')
                    ->label(__('appointment.fields.started_at'))
                    ->dateTime(),
                TextEntry::make('cancelled_at')
                    ->label(__('appointment.fields.cancelled_at'))
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->label(__('appointment.fields.created_at'))
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label(__('appointment.fields.updated_at'))
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->label(__('appointment.fields.deleted_at'))
                    ->dateTime(),
            ]);
    }
}
