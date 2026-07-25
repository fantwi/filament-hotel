<?php

namespace App\Filament\Admin\Resources\MenuCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenuCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable(),
                IconColumn::make('is_active')->boolean(),
                IconColumn::make('is_published')->label('Published')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable(),
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
