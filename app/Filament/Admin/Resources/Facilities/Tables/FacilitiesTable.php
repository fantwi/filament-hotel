<?php

namespace App\Filament\Admin\Resources\Facilities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

class FacilitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Facility Name')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_published')->label('Published')->boolean(),
            ])
            ->filters([
                //
            ])
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
