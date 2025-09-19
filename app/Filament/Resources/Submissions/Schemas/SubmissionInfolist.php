<?php

namespace App\Filament\Resources\Submissions\Schemas;

use App\Enums\Submission\Type;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('patient_id')
                    ->label(__('submission.fields.patient_id'))
                    ->numeric(),
                TextEntry::make('user_id')
                    ->label(__('submission.fields.user_id'))
                    ->numeric(),
                TextEntry::make('type')
                    ->label(__('submission.fields.type'))
                    ->formatStateUsing(fn (?int $state) => $state !== null ? Type::labels()[$state] ?? (string) $state : null),
                TextEntry::make('created_at')
                    ->label(__('submission.fields.created_at'))
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label(__('submission.fields.updated_at'))
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->label(__('submission.fields.deleted_at'))
                    ->dateTime(),
            ]);
    }
}
