<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\KitchenProduction;
use App\Models\KitchenStockMovement;
use App\Models\RestaurantOrder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides an operational summary for kitchen managers.
 */
class KitchenManagerStats extends StatsOverviewWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->hasAnyRole(['super_admin', 'admin', 'kitchen_manager'])
            && $user?->can('view kitchen dashboard'));
    }

    /**
     * Builds date-filtered kitchen operations stats.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $periodLabel = $this->dashboardDateRangeLabel();

        return [
            Stat::make('Orders waiting to start', number_format($this->ordersInStatus('confirmed')))
                ->description($periodLabel)
                ->icon('heroicon-o-clock')
                ->color('info'),
            Stat::make('Orders preparing', number_format($this->ordersInStatus('preparing')))
                ->description($periodLabel)
                ->icon('heroicon-o-fire')
                ->color('warning'),
            Stat::make('Orders ready to serve', number_format($this->ordersInStatus('ready')))
                ->description($periodLabel)
                ->icon('heroicon-o-bell-alert')
                ->color('success'),
            Stat::make('Production batches', number_format($this->forDashboardDateRange(KitchenProduction::query(), 'production_date')->count()))
                ->description($periodLabel)
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary'),
            Stat::make('Stock movements', number_format($this->forDashboardDateRange(KitchenStockMovement::query(), 'occurred_at')->count()))
                ->description($periodLabel)
                ->icon('heroicon-o-archive-box')
                ->color('danger'),
        ];
    }

    /**
     * Counts eligible kitchen orders in the selected period by status.
     */
    private function ordersInStatus(string $status): int
    {
        return $this->forDashboardDateRange(RestaurantOrder::kitchenQueue())
            ->where('status', $status)
            ->count();
    }
}
