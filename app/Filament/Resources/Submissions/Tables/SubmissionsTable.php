<?php

namespace App\Filament\Resources\Submissions\Tables;

use App\Enums\Submission\Type;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient_id')
                    ->label(__('submission.fields.patient_id'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('user_id')
                    ->label(__('submission.fields.user_id'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('submission.fields.type'))
                    ->formatStateUsing(fn (?string $state) => $state ? Type::labels()[$state] ?? $state : null)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('submission.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('submission.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('submission.fields.deleted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
