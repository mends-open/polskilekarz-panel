<?php

namespace App\Filament\Resources\Entities;

use App\Filament\Resources\Entities\Pages\CreateEntity;
use App\Filament\Resources\Entities\Pages\EditEntity;
use App\Filament\Resources\Entities\Pages\ListEntities;
use App\Filament\Resources\Entities\Pages\ViewEntity;
use App\Filament\Resources\Entities\Schemas\EntityForm;
use App\Filament\Resources\Entities\Schemas\EntityInfolist;
use App\Filament\Resources\Entities\Tables\EntitiesTable;
use App\Models\Entity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EntityResource extends Resource
{
    protected static ?string $model = Entity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Entity';

    public static function getModelLabel(): string
    {
        return __('entities.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('entities.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('entities.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return EntityForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EntityInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EntitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEntities::route('/'),
            'create' => CreateEntity::route('/create'),
            'view' => ViewEntity::route('/{record}'),
            'edit' => EditEntity::route('/{record}/edit'),
        ];
    }
}
