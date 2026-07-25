<?php

namespace App\Filament\Admin\Resources\MenuItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenuItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')->square(),
                TextColumn::make('category.name')->sortable()->searchable(),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('price')->money('GHS')->sortable(),
                IconColumn::make('is_available')->boolean(),
                IconColumn::make('is_featured')->boolean(),
                IconColumn::make('is_published')->label('Published')->boolean(),
                TextColumn::make('preparation_time')->suffix(' mins')->sortable(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
