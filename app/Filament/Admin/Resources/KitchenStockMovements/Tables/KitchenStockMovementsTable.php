<?php

namespace App\Filament\Admin\Resources\KitchenStockMovements\Tables;

use App\Models\KitchenStockMovement;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Configures Filament administration for kitchen stock movements table.
 */
class KitchenStockMovementsTable
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No kitchen stock movements found')
            ->emptyStateDescription('Stock receipts, consumption, and adjustments will appear here. Reset filters to review the full stock history.')
            ->emptyStateIcon('heroicon-o-arrows-right-left')
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
                TextColumn::make('mobile_summary')
                    ->label('Stock Movement')
                    ->extraHeaderAttributes(['hidden' => true])
                    ->state(fn (KitchenStockMovement $record): string => $record->ingredient?->name ?? 'Ingredient unavailable')
                    ->description(fn (KitchenStockMovement $record): string => sprintf(
                        '%s · %s · %s %s %s · Balance %s → %s %s',
                        $record->occurred_at?->format('M d, Y g:i A') ?? 'Date not recorded',
                        str($record->type)->replace('_', ' ')->title(),
                        self::formatQuantity((float) $record->quantity),
                        $record->ingredient?->unit ?: 'unit',
                        self::directionLabel($record->direction),
                        self::formatQuantity((float) $record->balance_before),
                        self::formatQuantity((float) $record->balance_after),
                        $record->ingredient?->unit ?: 'unit',
                    ))
                    ->wrap()
                    ->weight('bold')
                    ->hiddenFrom('md'),
                TextColumn::make('occurred_at')->dateTime('M d, Y g:i A')->sortable()->visibleFrom('md'),
                TextColumn::make('ingredient.name')->label('Ingredient')->searchable()->sortable()->visibleFrom('md'),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title())
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
                    })
                    ->visibleFrom('md'),
                TextColumn::make('direction')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::directionLabel($state))
                    ->color(fn (string $state): string => match ($state) {
                        KitchenStockMovement::DIRECTION_IN => 'success',
                        KitchenStockMovement::DIRECTION_OUT => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        KitchenStockMovement::DIRECTION_IN => 'heroicon-o-arrow-down-tray',
                        KitchenStockMovement::DIRECTION_OUT => 'heroicon-o-arrow-up-tray',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->visibleFrom('md'),
                TextColumn::make('quantity')
                    ->state(fn (KitchenStockMovement $record): string => sprintf(
                        '%s %s',
                        self::formatQuantity((float) $record->quantity),
                        $record->ingredient?->unit ?: 'unit',
                    ))
                    ->alignEnd()
                    ->visibleFrom('md'),
                TextColumn::make('balance_before')
                    ->label('Before')
                    ->state(fn (KitchenStockMovement $record): string => sprintf(
                        '%s %s',
                        self::formatQuantity((float) $record->balance_before),
                        $record->ingredient?->unit ?: 'unit',
                    ))
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'),
                TextColumn::make('balance_after')
                    ->label('After')
                    ->state(fn (KitchenStockMovement $record): string => sprintf(
                        '%s %s',
                        self::formatQuantity((float) $record->balance_after),
                        $record->ingredient?->unit ?: 'unit',
                    ))
                    ->alignEnd()
                    ->visibleFrom('md'),
                TextColumn::make('total_cost')->money('GHS')->placeholder('—')->toggleable(isToggledHiddenByDefault: true)->visibleFrom('md'),
                TextColumn::make('reference_number')->label('Reference')->placeholder('—')->searchable()->toggleable(isToggledHiddenByDefault: true)->visibleFrom('md'),
                TextColumn::make('performedBy.name')->label('Recorded By')->placeholder('System')->toggleable(isToggledHiddenByDefault: true)->visibleFrom('md'),
                TextColumn::make('notes')->wrap()->limit(60)->toggleable(isToggledHiddenByDefault: true)->visibleFrom('md'),
            ])
            ->filters([
                SelectFilter::make('ingredient_id')->label('Ingredient')->relationship('ingredient', 'name')->searchable()->preload(),
                SelectFilter::make('type')->options(['opening_stock' => 'Opening Stock', 'receipt' => 'Receipt', 'consumption' => 'Consumption', 'wastage' => 'Wastage', 'adjustment_in' => 'Adjustment In', 'adjustment_out' => 'Adjustment Out', 'reversal' => 'Reversal']),
                Filter::make('occurred_at')
                    ->label('Date range')
                    ->schema([
                        DatePicker::make('from')
                            ->label('From')
                            ->maxDate(fn (Get $get): mixed => $get('until')),
                        DatePicker::make('until')
                            ->label('Until')
                            ->minDate(fn (Get $get): mixed => $get('from')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['from'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('occurred_at', '>=', $date),
                        )
                        ->when(
                            $data['until'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('occurred_at', '<=', $date),
                        )),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->modalHeading(fn (KitchenStockMovement $record): string => 'Stock movement · '.($record->ingredient?->name ?? '#'.$record->getKey()))
                    ->modalWidth(Width::FiveExtraLarge),
            ], RecordActionsPosition::AfterColumns)
            ->stackedOnMobile();
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
