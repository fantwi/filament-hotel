<?php

namespace App\Filament\Admin\Resources\RestaurantOrderItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RestaurantOrderItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.order_number')->label('Order')->searchable(),
                TextColumn::make('menuItem.name')->label('Menu item')->searchable(),
                TextColumn::make('quantity')->sortable(),
                TextColumn::make('unit_price')->money('GHS'),
                TextColumn::make('total_price')->money('GHS'),
            ])
            ->filters([])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
