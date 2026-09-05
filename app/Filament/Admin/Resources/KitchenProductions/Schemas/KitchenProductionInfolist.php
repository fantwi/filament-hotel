<?php

namespace App\Filament\Admin\Resources\KitchenProductions\Schemas;

use App\Models\KitchenProduction;
use App\Models\KitchenProductionIngredient;
use App\Models\KitchenStockMovement;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * Configures the read-only operational and inventory history for a production batch.
 */
class KitchenProductionInfolist
{
    /**
     * Builds the responsive batch-details schema.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                Section::make('Batch overview')
                    ->description('Identity, ownership, and production timing for this finished-food batch.')
                    ->schema([
                        TextEntry::make('batch_reference')
                            ->label('Batch reference')
                            ->copyable()
                            ->weight('bold')
                            ->icon('heroicon-o-clipboard-document-check'),
                        TextEntry::make('batch_status')
                            ->label('Inventory status')
                            ->state(fn (KitchenProduction $record): string => $record->voided_at ? 'Voided' : 'Posted')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'Voided' ? 'danger' : 'success')
                            ->icon(fn (string $state): string => $state === 'Voided'
                                ? 'heroicon-o-x-circle'
                                : 'heroicon-o-check-circle'),
                        TextEntry::make('menuItem.name')
                            ->label('Menu item')
                            ->weight('semibold')
                            ->placeholder('Deleted menu item'),
                        TextEntry::make('menuItem.category.name')
                            ->label('Menu category')
                            ->placeholder('No category recorded'),
                        TextEntry::make('restaurant.name')
                            ->label('Restaurant')
                            ->placeholder('Legacy batch — restaurant not recorded'),
                        TextEntry::make('production_date')
                            ->label('Production date')
                            ->date('M d, Y'),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->columnSpan(['default' => 1, 'lg' => 2]),

                Section::make('Yield summary')
                    ->description('Finished-food output after recorded waste.')
                    ->schema([
                        TextEntry::make('quantity_produced_display')
                            ->label('Produced')
                            ->state(fn (KitchenProduction $record): string => static::formatQuantity(
                                $record,
                                (float) $record->quantity_produced,
                            ))
                            ->badge()
                            ->color('info'),
                        TextEntry::make('quantity_wasted_display')
                            ->label('Wasted')
                            ->state(fn (KitchenProduction $record): string => static::formatQuantity(
                                $record,
                                (float) $record->quantity_wasted,
                            ))
                            ->badge()
                            ->color(fn (KitchenProduction $record): string => match (true) {
                                (float) $record->quantity_wasted > (float) $record->quantity_produced => 'danger',
                                (float) $record->quantity_wasted > 0 => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('net_yield_display')
                            ->label('Net yield')
                            ->state(fn (KitchenProduction $record): string => static::netYield($record))
                            ->badge()
                            ->color(fn (string $state): string => $state === 'Invalid waste quantity'
                                ? 'danger'
                                : 'success'),
                        TextEntry::make('waste_percentage_display')
                            ->label('Waste percentage')
                            ->state(fn (KitchenProduction $record): string => static::wastePercentage($record))
                            ->badge()
                            ->color(fn (string $state): string => $state === 'Invalid waste quantity'
                                ? 'danger'
                                : ($state === '—' ? 'gray' : 'info')),
                    ]),

                Section::make('Ingredients consumed')
                    ->description('Actual raw ingredients recorded and deducted for this batch.')
                    ->schema([
                        RepeatableEntry::make('ingredients')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('ingredient.name')
                                    ->label('Ingredient')
                                    ->weight('semibold')
                                    ->placeholder('Deleted ingredient'),
                                TextEntry::make('quantity_used_display')
                                    ->label('Quantity used')
                                    ->state(fn (KitchenProductionIngredient $record): string => sprintf(
                                        '%s %s',
                                        number_format((float) $record->quantity_used, 3),
                                        $record->unit ?: ($record->ingredient?->unit ?? 'unit'),
                                    ))
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('notes')
                                    ->label('Usage notes')
                                    ->placeholder('No usage notes recorded')
                                    ->columnSpanFull(),
                            ])
                            ->columns(['default' => 1, 'sm' => 2])
                            ->grid(['default' => 1, 'xl' => 2])
                            ->placeholder('No ingredient consumption was recorded for this batch.'),
                    ])
                    ->columnSpanFull(),

                Section::make('Stock movement history')
                    ->description('Immutable consumption and reversal entries associated with this batch.')
                    ->schema([
                        RepeatableEntry::make('stockMovements')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('type')
                                    ->label('Movement')
                                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->headline()->toString())
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        KitchenStockMovement::TYPE_CONSUMPTION => 'danger',
                                        KitchenStockMovement::TYPE_REVERSAL => 'success',
                                        default => 'info',
                                    }),
                                TextEntry::make('ingredient.name')
                                    ->label('Ingredient')
                                    ->placeholder('Deleted ingredient'),
                                TextEntry::make('direction')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => Str::upper($state))
                                    ->color(fn (string $state): string => $state === KitchenStockMovement::DIRECTION_IN
                                        ? 'success'
                                        : 'danger'),
                                TextEntry::make('movement_quantity_display')
                                    ->label('Quantity')
                                    ->state(fn (KitchenStockMovement $record): string => sprintf(
                                        '%s %s',
                                        number_format((float) $record->quantity, 3),
                                        static::movementUnit($record),
                                    )),
                                TextEntry::make('balance_change_display')
                                    ->label('Stock balance')
                                    ->state(fn (KitchenStockMovement $record): string => sprintf(
                                        '%s → %s %s',
                                        number_format((float) $record->balance_before, 3),
                                        number_format((float) $record->balance_after, 3),
                                        static::movementUnit($record),
                                    )),
                                TextEntry::make('occurred_at')
                                    ->label('Occurred at')
                                    ->dateTime('M d, Y g:i A'),
                                TextEntry::make('performedBy.name')
                                    ->label('Recorded by')
                                    ->placeholder('System process'),
                                TextEntry::make('notes')
                                    ->label('Movement notes')
                                    ->placeholder('No movement notes recorded')
                                    ->columnSpanFull(),
                            ])
                            ->columns(['default' => 1, 'sm' => 2, 'lg' => 3])
                            ->grid(['default' => 1, 'xl' => 2])
                            ->placeholder('No stock movements are linked to this batch.'),
                    ])
                    ->columnSpanFull(),

                Section::make('Notes and audit')
                    ->description('Operational notes and record ownership.')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Production notes')
                            ->placeholder('No production notes recorded')
                            ->columnSpanFull(),
                        TextEntry::make('producer.name')
                            ->label('Produced by')
                            ->placeholder('System or unknown staff member'),
                        TextEntry::make('created_at')
                            ->label('Created at')
                            ->dateTime('M d, Y g:i A'),
                        TextEntry::make('updated_at')
                            ->label('Last updated')
                            ->dateTime('M d, Y g:i A'),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->columnSpan(['default' => 1, 'lg' => 2]),

                Section::make('Corrections')
                    ->description('Audit trail for inventory reversals and voiding.')
                    ->schema([
                        TextEntry::make('correction_status')
                            ->label('Correction status')
                            ->state(fn (KitchenProduction $record): string => static::correctionStatus($record))
                            ->badge()
                            ->color(fn (string $state): string => str_starts_with($state, 'Voided') ? 'danger' : 'gray'),
                        TextEntry::make('voided_at')
                            ->label('Voided at')
                            ->dateTime('M d, Y g:i A')
                            ->placeholder('Not voided'),
                        TextEntry::make('voidedBy.name')
                            ->label('Voided by')
                            ->placeholder('Not applicable'),
                        TextEntry::make('void_reason')
                            ->label('Void reason')
                            ->placeholder('No correction reason recorded'),
                    ]),
            ]);
    }

    /**
     * Formats a production quantity with the menu item's configured unit.
     */
    private static function formatQuantity(KitchenProduction $record, float $quantity): string
    {
        $unit = $record->menuItem?->production_unit ?: 'unit';

        return sprintf('%s %s', number_format($quantity, 3), Str::plural($unit, abs($quantity)));
    }

    /**
     * Returns usable production after finished-food waste.
     */
    private static function netYield(KitchenProduction $record): string
    {
        $produced = (float) $record->quantity_produced;
        $wasted = (float) $record->quantity_wasted;

        if ($wasted > $produced) {
            return 'Invalid waste quantity';
        }

        return static::formatQuantity($record, $produced - $wasted);
    }

    /**
     * Returns the proportion of finished production recorded as waste.
     */
    private static function wastePercentage(KitchenProduction $record): string
    {
        $produced = (float) $record->quantity_produced;
        $wasted = (float) $record->quantity_wasted;

        if ($wasted > $produced) {
            return 'Invalid waste quantity';
        }

        if ($produced <= 0) {
            return '—';
        }

        return number_format(($wasted / $produced) * 100, 2).'%';
    }

    /**
     * Distinguishes a void that restored stock from one with nothing to reverse.
     */
    private static function correctionStatus(KitchenProduction $record): string
    {
        if (! $record->voided_at) {
            return 'No corrections recorded';
        }

        $hasReversal = $record->relationLoaded('stockMovements')
            ? $record->stockMovements->contains('type', KitchenStockMovement::TYPE_REVERSAL)
            : $record->stockMovements()->where('type', KitchenStockMovement::TYPE_REVERSAL)->exists();

        return $hasReversal
            ? 'Voided and reversed'
            : 'Voided — no stock reversal recorded';
    }

    /**
     * Uses the production line's unit snapshot before mutable ingredient metadata.
     */
    private static function movementUnit(KitchenStockMovement $movement): string
    {
        if ($movement->reference instanceof KitchenProduction) {
            $productionLine = $movement->reference->ingredients
                ->firstWhere('ingredient_id', $movement->ingredient_id);

            if (filled($productionLine?->unit)) {
                return $productionLine->unit;
            }
        }

        return $movement->ingredient?->unit ?? 'unit';
    }
}
