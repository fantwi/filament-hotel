<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Ingredient;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides the kitchen stock stats Filament dashboard widget.
 */
class KitchenStockStats extends StatsOverviewWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Current ingredient stock';

    protected ?string $description = 'Current replenishment risks across active ingredients; independent of the dashboard period.';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view kitchen stock') ?? false;
    }

    /**
     * Builds and returns stats.
     */
    protected function getStats(): array
    {
        $stock = Ingredient::query()
            ->where('is_active', true)
            ->selectRaw('SUM(CASE WHEN current_stock <= 0 THEN 1 ELSE 0 END) as out_of_stock')
            ->selectRaw('SUM(CASE WHEN current_stock > 0 AND current_stock <= reorder_level THEN 1 ELSE 0 END) as low_stock')
            ->selectRaw('SUM(CASE WHEN current_stock > reorder_level THEN 1 ELSE 0 END) as healthy_stock')
            ->selectRaw('COALESCE(SUM(current_stock * unit_cost), 0) as inventory_value')
            ->first();

        return [
            Stat::make('Out of stock', number_format((int) $stock?->out_of_stock))
                ->description('Current balance is zero or below')
                ->icon('heroicon-o-exclamation-circle')
                ->color('danger'),
            Stat::make('Low stock', number_format((int) $stock?->low_stock))
                ->description('Above zero and at or below reorder level')
                ->icon('heroicon-o-arrow-trending-down')
                ->color('warning'),
            Stat::make('Healthy stock', number_format((int) $stock?->healthy_stock))
                ->description('Current balance is above reorder level')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Current inventory value', 'GHS '.number_format((float) $stock?->inventory_value, 2))
                ->description('Active ingredient stock at current cost')
                ->icon('heroicon-o-banknotes')
                ->color('primary'),
        ];
    }
}
