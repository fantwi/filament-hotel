<?php

namespace App\Filament\Admin\Resources\Restaurants\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Configures Filament administration for restaurants table.
 */
class RestaurantsTable
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                ImageColumn::make('hero_image'),

                TextColumn::make('capacity')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('opening_time')
                    ->label('Opens')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('closing_time')
                    ->label('Closes')
                    ->time('H:i')
                    ->sortable(),

                IconColumn::make('is_open')->boolean(),
                IconColumn::make('is_published')->label('Published')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_open')
                    ->label('Currently open'),
                TernaryFilter::make('is_published')
                    ->label('Published'),
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
