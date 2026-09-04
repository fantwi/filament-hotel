<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\RestaurantOrder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides a task-focused summary for kitchen staff.
 */
class KitchenStaffStats extends StatsOverviewWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '10s';

    protected ?string $heading = 'Current kitchen workload';

    protected ?string $description = 'Live order stages refresh every 10 seconds. Served orders use the selected dashboard period.';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->hasAnyRole(['super_admin', 'admin', 'kitchen_staff'])
            && $user?->can('view kitchen dashboard'));
    }

    /**
     * Builds date-filtered kitchen task stats.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $orderCounts = RestaurantOrder::kitchenQueue()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $currentQueueLabel = 'Current active queue';
        $periodLabel = $this->dashboardDateRangeLabel();

        return [
            Stat::make('Orders waiting to start', number_format((int) ($orderCounts['confirmed'] ?? 0)))
                ->description($currentQueueLabel)
                ->icon('heroicon-o-clock')
                ->color('info'),
            Stat::make('Orders preparing', number_format((int) ($orderCounts['preparing'] ?? 0)))
                ->description($currentQueueLabel)
                ->icon('heroicon-o-fire')
                ->color('warning'),
            Stat::make('Orders ready to serve', number_format((int) ($orderCounts['ready'] ?? 0)))
                ->description($currentQueueLabel)
                ->icon('heroicon-o-bell-alert')
                ->color('success'),
            Stat::make('Orders served', number_format($this->forDashboardDateRange(RestaurantOrder::query(), 'served_at')->where('status', 'served')->count()))
                ->description('Selected period: '.$periodLabel)
                ->icon('heroicon-o-check-circle')
                ->color('primary'),
        ];
    }
}
