<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\BuildsDashboardDrillDowns;
use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\CorporateOrganization;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use App\Services\CorporateCreditService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides the super-admin finance and governance stats overview widget.
 */
class SuperAdminFinanceStats extends StatsOverviewWidget
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
        $refunds = (clone $payments)->whereIn('payment_status', ['refunded', 'refund'])->sum('amount');
        $pending = (clone $payments)->whereIn('payment_status', ['pending', 'unpaid'])->count();
        $corporateAccountsAdded = $this->forDashboardDateRange(CorporateOrganization::query())
            ->where('is_credit_enabled', true)
            ->count();
        $receivables = $this->forDashboardDateRange(Booking::query())
            ->whereIn('payment_status', ['pending', 'unpaid'])
            ->whereNotIn('status', ['cancelled', 'expired', 'no_show'])
            ->sum('total_price')
            + $this->forDashboardDateRange(ConferenceBooking::query())
                ->whereIn('payment_status', ['pending', 'unpaid'])
                ->where('status', '!=', 'cancelled')
                ->sum('total_price')
            + $this->forDashboardDateRange(RestaurantReservation::query())
                ->whereIn('payment_status', ['pending', 'unpaid'])
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->sum('reservation_fee')
            + $this->forDashboardDateRange(RestaurantOrder::query())
                ->whereIn('payment_status', ['pending', 'unpaid'])
                ->where('status', '!=', 'cancelled')
                ->sum('total');

        return [
            Stat::make('Total Receivables', 'GHS '.number_format($receivables, 2))
                ->description('All valid unpaid transactions')
                ->icon('heroicon-o-receipt-percent')
                ->color('warning'),
            Stat::make('Corporate Outstanding', 'GHS '.number_format($corporate['outstanding'], 2))
                ->description('Corporate subset of total receivables')
                ->icon('heroicon-o-building-office')
                ->color('danger'),
            $this->drillDown(
                Stat::make('Pending Payments', number_format($pending))
                    ->description($periodLabel)
                    ->icon('heroicon-o-clock')
                    ->color('danger'),
                $this->paymentDrillDownUrl('pending'),
            ),
            $this->drillDown(
                Stat::make('Refunds', 'GHS '.number_format($refunds, 2))
                    ->description($periodLabel)
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('info'),
                $this->paymentDrillDownUrl('refunded'),
            ),
            Stat::make('Corporate Accounts Added', number_format($corporateAccountsAdded))
                ->description($periodLabel)
                ->icon('heroicon-o-building-office-2')
                ->color('primary'),
        ];
    }
}
