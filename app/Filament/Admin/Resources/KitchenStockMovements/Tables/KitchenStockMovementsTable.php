<?php

namespace App\Filament\Admin\Resources\KitchenStockMovements\Tables;

use App\Models\Ingredient;
use App\Models\KitchenProduction;
use App\Models\KitchenStockMovement;
use App\Models\Restaurant;
use App\Models\RestaurantOrder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\BaseFilter;
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
            ->emptyStateHeading(fn (HasTable $livewire): string => match (true) {
                $livewire->hasTableSearch() => 'No stock movements match your search',
                self::hasActiveTableFilters($livewire) => 'No stock movements match these filters',
                default => 'No stock movements recorded',
            })
            ->emptyStateDescription(fn (HasTable $livewire): string => match (true) {
                $livewire->hasTableSearch() => 'Clear the search to return to the complete stock ledger.',
                self::hasActiveTableFilters($livewire) => 'Reset the filters to return to the complete stock ledger.',
                default => 'Stock receipts, consumption, wastage, and adjustments will appear here automatically after stock activity is recorded.',
            })
            ->emptyStateIcon(fn (HasTable $livewire): string => match (true) {
                $livewire->hasTableSearch() => 'heroicon-o-magnifying-glass',
                self::hasActiveTableFilters($livewire) => 'heroicon-o-funnel',
                default => 'heroicon-o-arrows-right-left',
            })
            ->emptyStateActions([
                Action::make('clearSearch')
                    ->label('Clear search')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->visible(fn (HasTable $livewire): bool => $livewire->hasTableSearch())
                    ->action(fn (HasTable $livewire) => $livewire->resetTableSearch()),
                Action::make('resetFilters')
                    ->label('Reset filters')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->visible(fn (HasTable $livewire): bool => ! $livewire->hasTableSearch() && self::hasActiveTableFilters($livewire))
                    ->action(fn (HasTable $livewire) => $livewire->resetTableFiltersForm()),
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
                SelectFilter::make('direction')
                    ->options([
                        KitchenStockMovement::DIRECTION_IN => 'Stock in',
                        KitchenStockMovement::DIRECTION_OUT => 'Stock out',
                    ]),
                SelectFilter::make('restaurant')
                    ->options(fn (): array => Restaurant::query()
                        ->whereIn(
                            'id',
                            Ingredient::query()
                                ->select('restaurant_id')
                                ->whereNotNull('restaurant_id')
                                ->whereHas('stockMovements'),
                        )
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->whereHas(
                            'ingredient',
                            fn (Builder $query): Builder => $query->where('restaurant_id', $data['value']),
                        ),
                    )),
                SelectFilter::make('performed_by')
                    ->label('Recorded by')
                    ->options(fn (): array => User::query()
                        ->whereIn(
                            'id',
                            KitchenStockMovement::query()
                                ->select('performed_by')
                                ->whereNotNull('performed_by'),
                        )
                        ->orderBy('first_name')
                        ->orderBy('last_name')
                        ->get()
                        ->mapWithKeys(fn (User $user): array => [$user->id => $user->name])
                        ->all())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('source')
                    ->options(fn (): array => [
                        (new KitchenProduction)->getMorphClass() => 'Production batch',
                        (new RestaurantOrder)->getMorphClass() => 'Food order',
                        'manual' => 'Manual entry',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'manual' => $query->whereNull('reference_type'),
                        null, '' => $query,
                        default => $query->where('reference_type', $data['value']),
                    }),
                SelectFilter::make('date_preset')
                    ->label('Quick period')
                    ->options([
                        'today' => 'Today',
                        'last_7_days' => 'Last 7 days',
                        'this_month' => 'This month',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        [$start, $endExclusive] = self::datePresetBounds($data['value'] ?? null);

                        if ($start === null || $endExclusive === null) {
                            return $query;
                        }

                        return $query
                            ->where('occurred_at', '>=', $start)
                            ->where('occurred_at', '<', $endExclusive);
                    }),
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
                    ->columns(['default' => 1, 'sm' => 2])
                    ->columnSpan(['default' => 1, 'md' => 2])
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
            ->filtersFormColumns(['default' => 1, 'md' => 2, 'xl' => 3])
            ->filtersFormWidth(Width::FourExtraLarge)
            ->defaultSort('occurred_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->modalHeading(fn (KitchenStockMovement $record): string => 'Stock movement · '.($record->ingredient?->name ?? '#'.$record->getKey()))
                    ->modalWidth(Width::FiveExtraLarge),
            ], RecordActionsPosition::AfterColumns)
            ->stackedOnMobile();
    }

    /**
     * Determines whether any table filter currently narrows the ledger.
     */
    private static function hasActiveTableFilters(HasTable $livewire): bool
    {
        return collect($livewire->getTable()->getFilters())
            ->contains(fn (BaseFilter $filter): bool => $filter->getIndicators() !== []);
    }

    /**
     * Returns inclusive-start and exclusive-end bounds for a quick period.
     *
     * @return array{CarbonImmutable|null, CarbonImmutable|null}
     */
    private static function datePresetBounds(?string $preset): array
    {
        $today = today()->toImmutable()->startOfDay();

        return match ($preset) {
            'today' => [$today, $today->addDay()],
            'last_7_days' => [$today->subDays(6), $today->addDay()],
            'this_month' => [$today->startOfMonth(), $today->startOfMonth()->addMonth()],
            default => [null, null],
        };
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
