<?php

namespace App\Filament\Admin\Resources\ConferenceRooms\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Configures Filament administration for conference rooms table.
 */
class ConferenceRoomsTable
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Conference Room Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('capacity')
                    ->label('Capacity')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price_per_hour')
                    ->label('Price per Hour')
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
