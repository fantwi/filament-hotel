<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

/**
 * Summarizes the currently filtered kitchen stock-movement ledger.
 */
class KitchenStockMovementStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Ledger activity';

    protected ?string $description = 'These figures follow the active Stock Movements search and filters.';

    protected ?string $pollingInterval = null;

    /**
     * The parent list page calculates the summary once from its filtered table query.
     *
     * @var array<string, float|int|string>
     */
    #[Reactive]
    public array $stockMovementSummary = [];

    /**
     * Restricts the widget to users who may view the underlying ledger.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view kitchen stock movements') ?? false;
    }

    /**
     * Builds operational counts and values without adding incompatible stock units.
     *
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $summary = array_replace([
            'movement_count' => 0,
            'ingredient_count' => 0,
            'stock_in_count' => 0,
            'stock_out_count' => 0,
            'stock_in_value' => 0,
            'stock_out_value' => 0,
            'period_label' => 'All recorded dates',
        ], $this->stockMovementSummary);

        return [
            Stat::make('Movement entries', number_format((int) $summary['movement_count']))
                ->description($summary['period_label'].' · Current filtered scope')
                ->descriptionIcon('heroicon-m-funnel')
                ->icon('heroicon-o-arrows-right-left')
                ->color('primary'),
            Stat::make('Ingredients affected', number_format((int) $summary['ingredient_count']))
                ->description('Distinct ingredients in this scope')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->icon('heroicon-o-cube')
                ->color('info'),
            Stat::make('Stock-in value', 'GHS '.number_format((float) $summary['stock_in_value'], 2))
                ->description(number_format((int) $summary['stock_in_count']).' incoming '.str('entry')->plural((int) $summary['stock_in_count']))
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->icon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make('Stock-out value', 'GHS '.number_format((float) $summary['stock_out_value'], 2))
                ->description(number_format((int) $summary['stock_out_count']).' outgoing '.str('entry')->plural((int) $summary['stock_out_count']))
                ->descriptionIcon('heroicon-m-arrow-up-tray')
                ->icon('heroicon-o-receipt-percent')
                ->color('warning'),
        ];
    }
}
