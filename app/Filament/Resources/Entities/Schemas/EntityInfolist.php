<?php

namespace App\Filament\Resources\Entities\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class EntityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->dateTime(),
            ]);
    }
}
