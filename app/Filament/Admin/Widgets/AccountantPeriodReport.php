<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccountantPeriodReport extends StatsOverviewWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('view accountant dashboard') ?? false;
    }

    protected function getStats(): array
    {
        $payments = $this->forDashboardDateRange(Payment::query())
            ->whereIn('payment_status', ['paid', 'completed']);

        return [
            Stat::make('Revenue for Selected Range', 'GHS '.number_format((clone $payments)->sum('amount'), 2))->description($this->dashboardDateRangeLabel())->icon('heroicon-o-banknotes')->color('success'),
            Stat::make('Payments Received', number_format((clone $payments)->count()))->description('Completed payments')->icon('heroicon-o-credit-card')->color('primary'),
            Stat::make('Average Payment', 'GHS '.number_format((clone $payments)->avg('amount') ?? 0, 2))->description('Within selected range')->icon('heroicon-o-calculator')->color('info'),
            Stat::make('Refunds in Range', 'GHS '.number_format($this->forDashboardDateRange(Payment::query())->whereIn('payment_status', ['refunded', 'refund'])->sum('amount'), 2))->description($this->dashboardDateRangeLabel())->icon('heroicon-o-arrow-uturn-left')->color('warning'),
        ];
    }
}
