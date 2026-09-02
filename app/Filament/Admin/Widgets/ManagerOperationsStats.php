<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\BuildsDashboardDrillDowns;
use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use App\Models\KitchenProduction;
use App\Models\KitchenStockMovement;
use App\Models\RestaurantOrder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides a date-aware operations summary for the manager dashboard.
 */
class ManagerOperationsStats extends StatsOverviewWidget
{
    use BuildsDashboardDrillDowns;
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view manager dashboard') ?? false;
    }

    /**
     * Builds operational stats for the selected dashboard period.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        [$start, $end] = $this->dashboardDateRange();
        $periodLabel = $this->dashboardDateRangeLabel();

        return [
            $this->drillDown(
                Stat::make(
                    'Active stays',
                    number_format(Booking::query()
                        ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                        ->whereDate('check_in', '<=', $end->toDateString())
                        ->whereDate('check_out', '>', $start->toDateString())
                        ->count()),
                )
                    ->description($periodLabel)
                    ->icon('heroicon-o-key')
                    ->color('primary'),
                $this->bookingDrillDownUrl(),
            ),
            $this->drillDown(
                Stat::make(
                    'Active kitchen orders',
                    number_format($this->forDashboardDateRange(RestaurantOrder::query()->kitchenQueue())->count()),
                )
                    ->description($periodLabel)
                    ->icon('heroicon-o-fire')
                    ->color('success'),
                $this->restaurantOrderDrillDownUrl('kitchen_queue'),
            ),
            $this->drillDown(
                Stat::make(
                    'Production batches',
                    number_format($this->forDashboardDateRange(KitchenProduction::query(), 'production_date')->count()),
                )
                    ->description($periodLabel)
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('info'),
                $this->kitchenProductionDrillDownUrl(),
            ),
            $this->drillDown(
                Stat::make(
                    'Stock movements',
                    number_format($this->forDashboardDateRange(KitchenStockMovement::query(), 'occurred_at')->count()),
                )
                    ->description($periodLabel)
                    ->icon('heroicon-o-archive-box')
                    ->color('warning'),
                $this->kitchenStockMovementDrillDownUrl(),
            ),
        ];
    }
}
