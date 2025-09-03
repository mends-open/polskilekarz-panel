<?php

namespace App\Filament\Resources\Patients\Tables;

use App\Enums\Patient\PatientGender;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PatientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')
                    ->label(__('patients.fields.first_name'))
                    ->searchable(),
                TextColumn::make('last_name')
                    ->label(__('patients.fields.last_name'))
                    ->searchable(),
                TextColumn::make('birth_date')
                    ->label(__('patients.fields.birth_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('gender')
                    ->label(__('patients.fields.gender'))
                    ->enum(PatientGender::class)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('patients.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('patients.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('patients.fields.deleted_at'))
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
