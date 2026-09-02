<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\BuildsDashboardDrillDowns;
use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Filament\Admin\Pages\CorporateReceivables;
use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Services\TransactionDashboardSummary;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides a transaction summary stats overview for the dashboard.
 */
class TransactionStats extends StatsOverviewWidget
{
    use BuildsDashboardDrillDowns;
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
            $this->dashboardSectionDrillDown(
                Stat::make('All transactions created', number_format($totals['transactions']))
                    ->description("All statuses · transaction date: {$periodLabel}")
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('primary'),
                'transaction-mix',
            ),
            $this->dashboardSectionDrillDown(
                Stat::make('Active transaction value', $this->formatAmount($totals['gross']))
                    ->description("Active statuses · transaction date: {$periodLabel}")
                    ->icon('heroicon-o-banknotes')
                    ->color('info'),
                'transaction-breakdown',
            ),
            $this->paymentOrSectionDrillDown(
                Stat::make('Completed payments recorded', $this->formatAmount($totals['payments']))
                    ->description("Completed only · payment date: {$periodLabel}")
                    ->icon('heroicon-o-credit-card')
                    ->color('success'),
                'collected',
                'collection-performance',
            ),
            $this->dashboardSectionDrillDown(
                Stat::make('Outstanding from period', $this->formatAmount($totals['outstanding']))
                    ->description("Active unpaid · transaction date: {$periodLabel}")
                    ->icon('heroicon-o-clock')
                    ->color('warning'),
                'outstanding-follow-up',
            ),
            $this->corporateOrSectionDrillDown(
                Stat::make('Corporate outstanding from period', $this->formatAmount($totals['corporate_outstanding']))
                    ->description("Corporate subset · transaction date: {$periodLabel}")
                    ->icon('heroicon-o-building-office-2')
                    ->color('danger'),
                'outstanding-follow-up',
            ),
        ];
    }

    /**
     * Links payments only when the current user can open the payment register.
     */
    private function paymentOrSectionDrillDown(Stat $stat, string $status, string $section): Stat
    {
        return auth()->check() && PaymentResource::canViewAny()
            ? $this->drillDown($stat, $this->paymentDrillDownUrl($status))
            : $this->dashboardSectionDrillDown($stat, $section);
    }

    /**
     * Links receivables only when the current user can open the settlement queue.
     */
    private function corporateOrSectionDrillDown(Stat $stat, string $section): Stat
    {
        return auth()->check() && CorporateReceivables::canAccess()
            ? $this->drillDown($stat, $this->corporateReceivablesPeriodDrillDownUrl())
            : $this->dashboardSectionDrillDown($stat, $section);
    }

    /**
     * Formats an amount using the application's display currency.
     */
    private function formatAmount(float|int|string|null $amount): string
    {
        return 'GHS '.number_format((float) $amount, 2);
    }
}
