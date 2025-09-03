<?php

namespace App\Filament\Resources\Appointments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AppointmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('patient_id')
                    ->numeric(),
                TextEntry::make('user_id')
                    ->numeric(),
                TextEntry::make('type'),
                TextEntry::make('duration')
                    ->numeric(),
                TextEntry::make('scheduled_at')
                    ->dateTime(),
                TextEntry::make('confirmed_at')
                    ->dateTime(),
                TextEntry::make('started_at')
                    ->dateTime(),
                TextEntry::make('cancelled_at')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->dateTime(),
            ]);
    }
}
