<?php

namespace App\Filament\Admin\Resources\KitchenStockMovements\Schemas;

use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use App\Filament\Admin\Resources\RestaurantOrders\RestaurantOrderResource;
use App\Models\KitchenProduction;
use App\Models\KitchenStockMovement;
use App\Models\RestaurantOrder;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * Configures the complete read-only audit details for a kitchen stock movement.
 */
class KitchenStockMovementInfolist
{
    /**
     * Builds the responsive stock-movement details schema.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                Section::make('Movement overview')
                    ->description('Ingredient movement, quantity, balance, timing, and ownership.')
                    ->schema([
                        TextEntry::make('ingredient.name')
                            ->label('Ingredient')
                            ->weight('bold')
                            ->placeholder('Deleted ingredient'),
                        TextEntry::make('type')
                            ->label('Movement type')
                            ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->headline()->toString())
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                KitchenStockMovement::TYPE_RECEIPT => 'success',
                                KitchenStockMovement::TYPE_CONSUMPTION => 'danger',
                                KitchenStockMovement::TYPE_WASTAGE, KitchenStockMovement::TYPE_ADJUSTMENT_OUT => 'warning',
                                KitchenStockMovement::TYPE_ADJUSTMENT_IN => 'info',
                                KitchenStockMovement::TYPE_REVERSAL => 'primary',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                KitchenStockMovement::TYPE_RECEIPT => 'heroicon-o-arrow-down-tray',
                                KitchenStockMovement::TYPE_CONSUMPTION => 'heroicon-o-arrow-up-tray',
                                KitchenStockMovement::TYPE_WASTAGE => 'heroicon-o-trash',
                                KitchenStockMovement::TYPE_ADJUSTMENT_IN => 'heroicon-o-plus-circle',
                                KitchenStockMovement::TYPE_ADJUSTMENT_OUT => 'heroicon-o-minus-circle',
                                KitchenStockMovement::TYPE_REVERSAL => 'heroicon-o-arrow-uturn-left',
                                default => 'heroicon-o-archive-box',
                            }),
                        TextEntry::make('direction')
                            ->formatStateUsing(fn (string $state): string => self::directionLabel($state))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                KitchenStockMovement::DIRECTION_IN => 'success',
                                KitchenStockMovement::DIRECTION_OUT => 'danger',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                KitchenStockMovement::DIRECTION_IN => 'heroicon-o-arrow-down-tray',
                                KitchenStockMovement::DIRECTION_OUT => 'heroicon-o-arrow-up-tray',
                                default => 'heroicon-o-question-mark-circle',
                            }),
                        TextEntry::make('quantity_display')
                            ->label('Quantity')
                            ->state(fn (KitchenStockMovement $record): string => sprintf(
                                '%s %s',
                                self::formatQuantity((float) $record->quantity),
                                $record->ingredient?->unit ?: 'unit',
                            )),
                        TextEntry::make('balance_change_display')
                            ->label('Stock balance')
                            ->state(fn (KitchenStockMovement $record): string => sprintf(
                                '%s → %s %s',
                                self::formatQuantity((float) $record->balance_before),
                                self::formatQuantity((float) $record->balance_after),
                                $record->ingredient?->unit ?: 'unit',
                            )),
                        TextEntry::make('occurred_at')
                            ->label('Occurred at')
                            ->dateTime('M d, Y g:i A')
                            ->placeholder('Not recorded'),
                        TextEntry::make('performedBy.name')
                            ->label('Recorded by')
                            ->placeholder('System process'),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->columnSpan(['default' => 1, 'lg' => 2]),

                Section::make('Cost and source')
                    ->description('Purchase information and the operation that created this entry.')
                    ->schema([
                        TextEntry::make('unit_cost')
                            ->label('Unit cost')
                            ->money('GHS')
                            ->placeholder('Not recorded'),
                        TextEntry::make('total_cost')
                            ->label('Total cost')
                            ->money('GHS')
                            ->placeholder('Not recorded'),
                        TextEntry::make('supplier_name')
                            ->label('Supplier')
                            ->placeholder('Not recorded'),
                        TextEntry::make('reference_number')
                            ->label('Invoice / delivery reference')
                            ->copyable()
                            ->placeholder('Not recorded'),
                        TextEntry::make('source_type')
                            ->label('Linked source')
                            ->state(fn (KitchenStockMovement $record): string => match (true) {
                                $record->reference instanceof KitchenProduction => 'Kitchen production',
                                $record->reference instanceof RestaurantOrder => 'Food order',
                                filled($record->reference_type) => str(class_basename($record->reference_type))->headline()->toString(),
                                default => 'Manual stock entry',
                            }),
                        TextEntry::make('source_reference')
                            ->label('Source reference')
                            ->state(fn (KitchenStockMovement $record): string => match (true) {
                                $record->reference instanceof KitchenProduction => $record->reference->batch_reference ?: '#'.$record->reference->getKey(),
                                $record->reference instanceof RestaurantOrder => $record->reference->order_number ?: '#'.$record->reference->getKey(),
                                filled($record->reference_number) => $record->reference_number,
                                filled($record->reference_id) => '#'.$record->reference_id,
                                default => 'No linked record',
                            })
                            ->url(fn (KitchenStockMovement $record): ?string => match (true) {
                                $record->reference instanceof KitchenProduction && KitchenProductionResource::canView($record->reference) => KitchenProductionResource::getUrl('view', ['record' => $record->reference]),
                                $record->reference instanceof RestaurantOrder && RestaurantOrderResource::canEdit($record->reference) => RestaurantOrderResource::getUrl('edit', ['record' => $record->reference]),
                                default => null,
                            })
                            ->icon(fn (KitchenStockMovement $record): ?string => $record->reference instanceof Model ? 'heroicon-o-arrow-top-right-on-square' : null),
                    ]),

                Section::make('Notes and audit')
                    ->description('Complete notes and immutable record timestamps.')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Movement notes')
                            ->placeholder('No movement notes recorded')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->label('Created at')
                            ->dateTime('M d, Y g:i A')
                            ->placeholder('Not recorded'),
                        TextEntry::make('updated_at')
                            ->label('Last updated')
                            ->dateTime('M d, Y g:i A')
                            ->placeholder('Not recorded'),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Formats stock quantities with meaningful precision up to three decimals.
     */
    private static function formatQuantity(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 3), '0'), '.');
    }

    /**
     * Converts the stored ledger direction into an operational label.
     */
    private static function directionLabel(string $direction): string
    {
        return match ($direction) {
            KitchenStockMovement::DIRECTION_IN => 'Stock in',
            KitchenStockMovement::DIRECTION_OUT => 'Stock out',
            default => str($direction)->headline()->toString(),
        };
    }
}
