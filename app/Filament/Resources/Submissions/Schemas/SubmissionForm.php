<?php

namespace App\Filament\Resources\Submissions\Schemas;

use App\Enums\Submission\Type;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('patient_id')
                    ->label(__('submission.fields.patient_id'))
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->label(__('submission.fields.user_id'))
                    ->required()
                    ->numeric(),
                Select::make('type')
                    ->label(__('submission.fields.type'))
                    ->options(Type::class)
                    ->required(),
                TextInput::make('data')
                    ->label(__('submission.fields.data')),
            ]);
    }
}
