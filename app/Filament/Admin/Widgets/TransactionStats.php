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
        return auth()->user()?->can('view transaction dashboard') ?? false;
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
            Stat::make('All transactions created', number_format($totals['transactions']))
                ->description("All statuses · transaction date: {$periodLabel}")
                ->icon('heroicon-o-arrows-right-left')
                ->color('primary'),
            Stat::make('Active transaction value', $this->formatAmount($totals['gross']))
                ->description("Active statuses · transaction date: {$periodLabel}")
                ->icon('heroicon-o-banknotes')
                ->color('info'),
            Stat::make('Completed payments recorded', $this->formatAmount($totals['payments']))
                ->description("Completed only · payment date: {$periodLabel}")
                ->icon('heroicon-o-credit-card')
                ->color('success'),
            Stat::make('Outstanding from period', $this->formatAmount($totals['outstanding']))
                ->description("Active unpaid · transaction date: {$periodLabel}")
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Corporate outstanding from period', $this->formatAmount($totals['corporate_outstanding']))
                ->description("Corporate subset · transaction date: {$periodLabel}")
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
