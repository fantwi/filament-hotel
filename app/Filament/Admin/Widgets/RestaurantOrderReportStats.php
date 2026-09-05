<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages\RestaurantOrderReport;
use App\Support\Reporting\ReportPeriod;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides a period-aware order summary for the restaurant report.
 */
class RestaurantOrderReportStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    /**
     * The report period selected on the parent restaurant report page.
     */
    public string $period = 'monthly';

    public string $startDate = '';

    public string $endDate = '';

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
        $reportPage = new RestaurantOrderReport;
        $reportPage->period = $this->validPeriod($this->period);
        $reportPage->startDate = $this->startDate;
        $reportPage->endDate = $this->endDate;
        $report = $reportPage->getReportMetrics();
        $periodLabel = $reportPage->periodLabel();

        return [
            Stat::make('Orders received', number_format((int) $report['totalOrders']))
                ->description(number_format((int) $report['totalItems']).' item(s) in '.$periodLabel)
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

    /**
     * Keeps nested widget state within the periods supported by the report page.
     */
    private function validPeriod(string $period): string
    {
        return array_key_exists($period, ReportPeriod::options())
            ? $period
            : 'monthly';
    }
}
