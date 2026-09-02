<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\KitchenStockMovement;
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
        $movements = $this->forDashboardDateRange(KitchenStockMovement::query(), 'occurred_at');

        return [
            Stat::make('Ingredients Moved', number_format((clone $movements)->distinct('ingredient_id')->count('ingredient_id')))->description($this->dashboardDateRangeLabel())->icon('heroicon-o-archive-box')->color('primary'),
            Stat::make('Stock Received', number_format((clone $movements)->where('direction', KitchenStockMovement::DIRECTION_IN)->sum('quantity'), 3))->description('Units received or adjusted in')->icon('heroicon-o-arrow-down-tray')->color('success'),
            Stat::make('Stock Consumed', number_format((clone $movements)->where('type', KitchenStockMovement::TYPE_CONSUMPTION)->sum('quantity'), 3))->description('Units used in production')->icon('heroicon-o-fire')->color('warning'),
            Stat::make('Stock Wasted', number_format((clone $movements)->where('type', KitchenStockMovement::TYPE_WASTAGE)->sum('quantity'), 3))->description('Units recorded as waste')->icon('heroicon-o-exclamation-triangle')->color('danger'),
        ];
    }
}
