<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages\RevenueReport;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides a period-aware financial summary for the revenue report.
 */
class RevenueReportStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    /**
     * The report period selected on the parent revenue report page.
     */
    public string $period = 'this_month';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    /**
     * Builds the financial metrics for the selected reporting period.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $reportPage = new RevenueReport;
        $reportPage->period = $this->validPeriod($this->period);
        $report = $reportPage->report();
        $periodLabel = $reportPage->periodLabel();

        return [
            Stat::make('Revenue received', 'GHS '.number_format((float) $report['revenue'], 2))
                ->description(number_format((int) $report['paymentsReceived']).' payment(s) in '.$periodLabel)
                ->icon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make('Refunds', 'GHS '.number_format((float) $report['refunds'], 2))
                ->description(number_format((int) $report['refundCount']).' refund(s) in '.$periodLabel)
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger'),
            Stat::make('Net revenue', 'GHS '.number_format((float) $report['netRevenue'], 2))
                ->description('Revenue less refunds')
                ->icon('heroicon-o-chart-bar')
                ->color('primary'),
            Stat::make('Outstanding balance', 'GHS '.number_format((float) $report['outstanding'], 2))
                ->description('Unpaid transactions in '.$periodLabel)
                ->icon('heroicon-o-clock')
                ->color('warning'),
        ];
    }

    /**
     * Keeps nested widget state within the periods supported by the report page.
     */
    private function validPeriod(string $period): string
    {
        return in_array($period, ['today', 'this_week', 'this_month', 'this_quarter', 'this_year', 'all'], true)
            ? $period
            : 'this_month';
    }
}
