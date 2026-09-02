<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Services\TransactionDashboardSummary;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides a transaction summary stats overview for the dashboard.
 */
class TransactionStats extends StatsOverviewWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    /**
     * Builds date-filtered transaction summary stats.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        [$start, $end] = $this->dashboardDateRange();
        $totals = app(TransactionDashboardSummary::class)
            ->summarize($start, $end)['totals'];
        $periodLabel = $this->dashboardDateRangeLabel();

        return [
            Stat::make('Transactions created', number_format($totals['transactions']))
                ->description($periodLabel)
                ->icon('heroicon-o-arrows-right-left')
                ->color('primary'),
            Stat::make('Gross transaction value', $this->formatAmount($totals['gross']))
                ->description('Excludes cancelled transactions')
                ->icon('heroicon-o-banknotes')
                ->color('info'),
            Stat::make('Payments received', $this->formatAmount($totals['payments']))
                ->description($periodLabel)
                ->icon('heroicon-o-credit-card')
                ->color('success'),
            Stat::make('Outstanding balance', $this->formatAmount($totals['outstanding']))
                ->description('Unpaid transactions in range')
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Corporate outstanding', $this->formatAmount($totals['corporate_outstanding']))
                ->description('Included in outstanding balance')
                ->icon('heroicon-o-building-office-2')
                ->color('danger'),
        ];
    }

    /**
     * Formats an amount using the application's display currency.
     */
    private function formatAmount(float|int|string|null $amount): string
    {
        return 'GHS '.number_format((float) $amount, 2);
    }
}
