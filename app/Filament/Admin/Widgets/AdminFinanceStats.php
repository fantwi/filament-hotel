<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Payment;
use App\Services\CorporateCreditService;
use App\Services\PaymentReportFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides an at-a-glance finance summary for administrators.
 */
class AdminFinanceStats extends StatsOverviewWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view admin dashboard') ?? false;
    }

    /**
     * Builds date-filtered finance stats.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        [$start, $end] = $this->dashboardDateRange();
        $periodLabel = $this->dashboardDateRangeLabel();
        $payments = $this->forDashboardDateRange(Payment::query());
        $corporate = app(CorporateCreditService::class)->dashboardOverview($start, $end);

        return [
            Stat::make('Collected Revenue', 'GHS '.number_format((clone $payments)->whereIn('payment_status', ['paid', 'completed'])->sum('amount'), 2))
                ->description($periodLabel)
                ->icon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make('Pending Payments', number_format(PaymentReportFilters::applyStatus(clone $payments, 'pending')->count()))
                ->description($periodLabel)
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Refunds', 'GHS '.number_format((clone $payments)->whereIn('payment_status', ['refunded', 'refund'])->sum('amount'), 2))
                ->description($periodLabel)
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger'),
            Stat::make('Corporate Outstanding', 'GHS '.number_format($corporate['outstanding'], 2))
                ->description('Current unpaid balance across all periods')
                ->icon('heroicon-o-building-office-2')
                ->color('info'),
        ];
    }
}
