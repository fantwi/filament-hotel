<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Payment;
use App\Services\CorporateCreditService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides the super-admin finance and governance stats overview widget.
 */
class SuperAdminFinanceStats extends StatsOverviewWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view super admin dashboard') ?? false;
    }

    /**
     * Builds finance and corporate-credit stats for the selected dashboard period.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        [$start, $end] = $this->dashboardDateRange();
        $periodLabel = $this->dashboardDateRangeLabel();
        $corporate = app(CorporateCreditService::class)->dashboardOverview($start, $end);
        $payments = $this->forDashboardDateRange(Payment::query());
        $revenue = (clone $payments)->whereIn('payment_status', ['paid', 'completed'])->sum('amount');
        $refunds = (clone $payments)->whereIn('payment_status', ['refunded', 'refund'])->sum('amount');
        $pending = (clone $payments)->where('payment_status', 'pending')->count();

        return [
            Stat::make('Paid Revenue', 'GHS '.number_format($revenue, 2))
                ->description($periodLabel)
                ->icon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make('Corporate Outstanding', 'GHS '.number_format($corporate['outstanding'], 2))
                ->description($periodLabel)
                ->icon('heroicon-o-building-office')
                ->color('warning'),
            Stat::make('Pending Payments', number_format($pending))
                ->description($periodLabel)
                ->icon('heroicon-o-clock')
                ->color('danger'),
            Stat::make('Refunds', 'GHS '.number_format($refunds, 2))
                ->description($periodLabel)
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('info'),
            Stat::make('Corporate Accounts', number_format($corporate['active_accounts']))
                ->description('Currently credit-enabled')
                ->icon('heroicon-o-building-office-2')
                ->color('primary'),
        ];
    }
}
