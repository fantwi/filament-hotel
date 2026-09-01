<?php

namespace App\Filament\Admin\Resources\RestaurantOrderItems\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Configures Filament administration for restaurant order items table.
 */
class RestaurantOrderItemsTable
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No restaurant order items found')
            ->emptyStateDescription('Items will appear as restaurant orders are placed. Reset filters to review the complete order detail.')
            ->emptyStateIcon('heroicon-o-queue-list')
            ->emptyStateActions([
                Action::make('resetFilters')
                    ->label('Reset filters')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function ($livewire): void {
                        $livewire->resetTableFiltersForm();
                    }),
            ])
            ->columns([
                TextColumn::make('order.order_number')->label('Order')->searchable(),
                TextColumn::make('menuItem.name')->label('Menu item')->searchable(),
                TextColumn::make('quantity')->sortable(),
                TextColumn::make('unit_price')->money('GHS')->sortable(),
                TextColumn::make('total_price')->money('GHS')->sortable(),
            ])
            ->filters([
                SelectFilter::make('order')
                    ->relationship('order', 'order_number')
                    ->searchable(),
                SelectFilter::make('menu_item')
                    ->relationship('menuItem', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
