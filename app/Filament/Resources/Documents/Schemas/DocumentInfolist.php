<?php

namespace App\Filament\Resources\Documents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('patient_id')
                    ->label(__('document.fields.patient_id'))
                    ->numeric(),
                TextEntry::make('user_id')
                    ->label(__('document.fields.user_id'))
                    ->numeric(),
                TextEntry::make('created_at')
                    ->label(__('document.fields.created_at'))
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label(__('document.fields.updated_at'))
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->label(__('document.fields.deleted_at'))
                    ->dateTime(),
            ]);
    }
}
