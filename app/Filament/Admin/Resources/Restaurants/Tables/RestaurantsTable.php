<?php

namespace App\Filament\Admin\Resources\Restaurants\Tables;

use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use Filament\Actions\Action;
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
            ->emptyStateHeading('No restaurants found')
            ->emptyStateDescription('Create a restaurant or reset filters to manage dining venues.')
            ->emptyStateIcon('heroicon-o-building-storefront')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Create restaurant')
                    ->icon('heroicon-o-plus')
                    ->url(fn (): string => RestaurantResource::getUrl('create'))
                    ->visible(fn (): bool => RestaurantResource::canCreate()),
                Action::make('resetFilters')
                    ->label('Reset filters')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function ($livewire): void {
                        $livewire->resetTableFiltersForm();
                    }),
            ])
            ->columns([
                //
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                ImageColumn::make('hero_image')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('capacity')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
