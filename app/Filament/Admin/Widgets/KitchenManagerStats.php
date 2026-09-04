<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\BuildsDashboardDrillDowns;
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
    use BuildsDashboardDrillDowns;
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Kitchen operations summary';

    protected ?string $description = 'Selected-period order, production-batch, and stock-movement activity.';

    /** @var array<string, int> */
    protected int|array|null $columns = [
        'default' => 1,
        'md' => 2,
        'xl' => 5,
    ];

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
        $orderCounts = $this->forDashboardDateRange(RestaurantOrder::kitchenQueue())
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $periodLabel = $this->dashboardDateRangeLabel();

        return [
            $this->drillDown(
                Stat::make('Orders waiting to start', number_format((int) ($orderCounts['confirmed'] ?? 0)))
                    ->description($periodLabel)
                    ->icon('heroicon-o-clock')
                    ->color('info'),
                $this->restaurantOrderDrillDownUrl('confirmed'),
            ),
            $this->drillDown(
                Stat::make('Orders preparing', number_format((int) ($orderCounts['preparing'] ?? 0)))
                    ->description($periodLabel)
                    ->icon('heroicon-o-fire')
                    ->color('warning'),
                $this->restaurantOrderDrillDownUrl('preparing'),
            ),
            $this->drillDown(
                Stat::make('Orders ready to serve', number_format((int) ($orderCounts['ready'] ?? 0)))
                    ->description($periodLabel)
                    ->icon('heroicon-o-bell-alert')
                    ->color('success'),
                $this->restaurantOrderDrillDownUrl('ready'),
            ),
            $this->drillDown(
                Stat::make('Production batches', number_format($this->forDashboardDateRange(KitchenProduction::query(), 'production_date')->count()))
                    ->description($periodLabel)
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('primary'),
                $this->kitchenProductionDrillDownUrl(),
            ),
            $this->drillDown(
                Stat::make('Stock movements', number_format($this->forDashboardDateRange(KitchenStockMovement::query(), 'occurred_at')->count()))
                    ->description($periodLabel)
                    ->icon('heroicon-o-archive-box')
                    ->color('gray'),
                $this->kitchenStockMovementDrillDownUrl(),
            ),
        ];
    }
}
