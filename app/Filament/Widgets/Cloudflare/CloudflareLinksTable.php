<?php

namespace App\Filament\Widgets\Cloudflare;

use App\Filament\Widgets\BaseTableWidget;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Table;

class CloudflareLinksTable extends BaseTableWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
