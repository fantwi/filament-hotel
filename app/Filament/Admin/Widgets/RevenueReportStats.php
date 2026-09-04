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

    /** @var array<string, string> */
    public array $drillDownUrls = [];

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
        $netRevenue = (float) $this->reportData['netRevenue'];
        $netRevenuePresentation = match (true) {
            $netRevenue > 0 => [
                'color' => 'success',
                'icon' => 'heroicon-o-arrow-trending-up',
                'description' => 'Positive after refunds',
            ],
            $netRevenue < 0 => [
                'color' => 'danger',
                'icon' => 'heroicon-o-arrow-trending-down',
                'description' => 'Refunds exceed collected revenue',
            ],
            default => [
                'color' => 'gray',
                'icon' => 'heroicon-o-scale',
                'description' => 'Collected revenue equals refunds',
            ],
        };

        return [
            $this->withDrillDown(
                Stat::make('Revenue received', 'GHS '.number_format((float) $this->reportData['revenue'], 2))
                    ->description(number_format((int) $this->reportData['paymentsReceived']).' payment(s) in '.$this->reportPeriodLabel)
                    ->icon('heroicon-o-banknotes')
                    ->color('success'),
                'revenue',
            ),
            $this->withDrillDown(
                Stat::make('Refunds', 'GHS '.number_format((float) $this->reportData['refunds'], 2))
                    ->description(number_format((int) $this->reportData['refundCount']).' refund(s) in '.$this->reportPeriodLabel)
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger'),
                'refunds',
            ),
            $this->withDrillDown(
                Stat::make('Net revenue', 'GHS '.number_format($netRevenue, 2))
                    ->description($netRevenuePresentation['description'])
                    ->icon($netRevenuePresentation['icon'])
                    ->color($netRevenuePresentation['color']),
                'netRevenue',
            ),
            $this->withDrillDown(
                Stat::make('Outstanding balance', 'GHS '.number_format((float) $this->reportData['outstanding'], 2))
                    ->description('Unpaid transactions in '.$this->reportPeriodLabel)
                    ->icon('heroicon-o-clock')
                    ->color('warning'),
                'outstanding',
            ),
        ];
    }

    /**
     * Makes a metric visibly actionable when an authorized destination exists.
     */
    private function withDrillDown(Stat $stat, string $key): Stat
    {
        $url = $this->drillDownUrls[$key] ?? null;

        return filled($url)
            ? $stat->url($url)->descriptionIcon('heroicon-m-arrow-top-right-on-square')
            : $stat;
    }
}
