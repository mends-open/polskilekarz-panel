<?php

namespace App\Filament\Resources\Appointments\Tables;

use App\Enums\Appointment\Type;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient_id')
                    ->label(__('appointments.fields.patient_id'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('user_id')
                    ->label(__('appointments.fields.user_id'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('appointments.fields.type'))
                    ->formatStateUsing(fn (?string $state) => $state ? Type::labels()[$state] ?? $state : null)
                    ->searchable(),
                TextColumn::make('duration')
                    ->label(__('appointments.fields.duration'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('scheduled_at')
                    ->label(__('appointments.fields.scheduled_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('confirmed_at')
                    ->label(__('appointments.fields.confirmed_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('appointments.fields.started_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('cancelled_at')
                    ->label(__('appointments.fields.cancelled_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('appointments.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('appointments.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('appointments.fields.deleted_at'))
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
