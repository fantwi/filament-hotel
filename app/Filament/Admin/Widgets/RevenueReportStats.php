<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides a period-aware financial summary for the revenue report.
 */
class RevenueReportStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    /** @var array{revenue: float, paymentsReceived: int, refunds: float, refundCount: int, netRevenue: float, outstanding: float} */
    public array $reportData = [
        'revenue' => 0.0,
        'paymentsReceived' => 0,
        'refunds' => 0.0,
        'refundCount' => 0,
        'netRevenue' => 0.0,
        'outstanding' => 0.0,
    ];

    public string $reportPeriodLabel = 'Selected period';

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
        return [
            Stat::make('Revenue received', 'GHS '.number_format((float) $this->reportData['revenue'], 2))
                ->description(number_format((int) $this->reportData['paymentsReceived']).' payment(s) in '.$this->reportPeriodLabel)
                ->icon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make('Refunds', 'GHS '.number_format((float) $this->reportData['refunds'], 2))
                ->description(number_format((int) $this->reportData['refundCount']).' refund(s) in '.$this->reportPeriodLabel)
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger'),
            Stat::make('Net revenue', 'GHS '.number_format((float) $this->reportData['netRevenue'], 2))
                ->description('Revenue less refunds')
                ->icon('heroicon-o-chart-bar')
                ->color('primary'),
            Stat::make('Outstanding balance', 'GHS '.number_format((float) $this->reportData['outstanding'], 2))
                ->description('Unpaid transactions in '.$this->reportPeriodLabel)
                ->icon('heroicon-o-clock')
                ->color('warning'),
        ];
    }
}
