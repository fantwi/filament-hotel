<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Str;

/**
 * Provides a period-aware order summary for the restaurant report.
 */
class RestaurantOrderReportStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    /** @var array{totalOrders: int, totalItems: int, revenue: float, refunds: float, netRevenue: float, outstanding: float, averageOrderValue: float, paymentRate: float} */
    public array $reportData = [
        'totalOrders' => 0,
        'totalItems' => 0,
        'revenue' => 0.0,
        'refunds' => 0.0,
        'netRevenue' => 0.0,
        'outstanding' => 0.0,
        'averageOrderValue' => 0.0,
        'paymentRate' => 0.0,
    ];

    public string $reportPeriodLabel = 'Monthly';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->can('view restaurant reports')
            || $user?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant'])
            || false;
    }

    /**
     * Builds restaurant order metrics for the selected reporting period.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $report = $this->reportData;
        $totalItems = (int) $report['totalItems'];

        return [
            Stat::make('Orders received', number_format((int) $report['totalOrders']))
                ->description(number_format($totalItems).' '.Str::plural('item', $totalItems).' in '.$this->reportPeriodLabel)
                ->icon('heroicon-o-shopping-bag')
                ->color('primary'),
            Stat::make('Net revenue', 'GHS '.number_format((float) $report['netRevenue'], 2))
                ->description('GHS '.number_format((float) $report['revenue'], 2).' collected · GHS '.number_format((float) $report['refunds'], 2).' refunded')
                ->icon('heroicon-o-banknotes')
                ->color($report['netRevenue'] < 0 ? 'danger' : 'success'),
            Stat::make('Outstanding balance', 'GHS '.number_format((float) $report['outstanding'], 2))
                ->description('Remaining balance on open orders')
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Average collected order', 'GHS '.number_format((float) $report['averageOrderValue'], 2))
                ->description(number_format((float) $report['paymentRate'], 1).'% payment completion')
                ->icon('heroicon-o-calculator')
                ->color('info'),
        ];
    }
}
