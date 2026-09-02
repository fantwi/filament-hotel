<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use App\Models\KitchenProduction;
use App\Models\KitchenStockMovement;
use App\Models\RestaurantOrder;
use App\Services\CorporateCreditService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides a date-aware operations summary for the manager dashboard.
 */
class ManagerOperationsStats extends StatsOverviewWidget
{
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
        $corporate = app(CorporateCreditService::class)->dashboardOverview($start, $end);

        return [
            Stat::make(
                'Active stays',
                number_format($this->forDashboardDateRange(Booking::query())->whereIn('status', ['pending', 'confirmed', 'checked_in'])->count()),
            )
                ->description($periodLabel)
                ->icon('heroicon-o-key')
                ->color('primary'),
            Stat::make(
                'Active kitchen orders',
                number_format($this->forDashboardDateRange(RestaurantOrder::query())->whereIn('status', ['confirmed', 'preparing', 'ready'])->count()),
            )
                ->description($periodLabel)
                ->icon('heroicon-o-fire')
                ->color('success'),
            Stat::make(
                'Production batches',
                number_format($this->forDashboardDateRange(KitchenProduction::query(), 'production_date')->count()),
            )
                ->description($periodLabel)
                ->icon('heroicon-o-clipboard-document-list')
                ->color('info'),
            Stat::make(
                'Stock movements',
                number_format($this->forDashboardDateRange(KitchenStockMovement::query(), 'occurred_at')->count()),
            )
                ->description($periodLabel)
                ->icon('heroicon-o-archive-box')
                ->color('warning'),
            Stat::make('Corporate outstanding', 'GHS '.number_format($corporate['period_outstanding'], 2))
                ->description($periodLabel)
                ->icon('heroicon-o-building-office-2')
                ->color('danger'),
        ];
    }
}
