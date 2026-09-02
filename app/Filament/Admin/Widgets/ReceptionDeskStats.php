<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\BuildsDashboardDrillDowns;
use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Query\JoinClause;

/**
 * Provides actionable front-desk stats for the reception dashboard.
 */
class ReceptionDeskStats extends StatsOverviewWidget
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
        return auth()->user()?->can('view reception dashboard') ?? false;
    }

    /**
     * Builds date-filtered front-desk action stats.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        [$start, $end] = $this->dashboardDateRange();
        $from = $start->toDateString();
        $until = $end->toDateString();
        $periodLabel = $this->dashboardDateRangeLabel();

        $activeStays = Booking::query()
            ->where('status', 'checked_in')
            ->whereDate('check_in', '<=', $until)
            ->whereDate('check_out', '>', $from)
            ->count();
        $pendingArrivals = Booking::query()
            ->whereBetween('check_in', [$from, $until])
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();
        $pendingDepartures = Booking::query()
            ->whereBetween('check_out', [$from, $until])
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->count();
        $unpaidArrivals = $this->unpaidArrivalBalance($from, $until);

        return [
            $this->drillDown(
                Stat::make('Checked-in stays', number_format($activeStays))
                    ->description($periodLabel)
                    ->icon('heroicon-o-user-group')
                    ->color('success'),
                $this->bookingDrillDownUrl('checked_in'),
            ),
            $this->drillDown(
                Stat::make('Pending arrivals', number_format($pendingArrivals))
                    ->description($periodLabel)
                    ->icon('heroicon-o-arrow-right-end-on-rectangle')
                    ->color('primary'),
                $this->bookingArrivalsDrillDownUrl(),
            ),
            $this->drillDown(
                Stat::make('Pending departures', number_format($pendingDepartures))
                    ->description($periodLabel)
                    ->icon('heroicon-o-arrow-left-start-on-rectangle')
                    ->color('warning'),
                $this->bookingDeparturesDrillDownUrl(),
            ),
            $this->drillDown(
                Stat::make('Unpaid arrival balance', 'GHS '.number_format((float) $unpaidArrivals, 2))
                    ->description('Requires payment follow-up')
                    ->icon('heroicon-o-credit-card')
                    ->color('danger'),
                $this->bookingOutstandingArrivalsDrillDownUrl(),
            ),
        ];
    }

    /**
     * Calculate the remaining balance for valid arrivals in one aggregate query.
     */
    private function unpaidArrivalBalance(string $from, string $until): float
    {
        $paidPayments = Payment::query()
            ->selectRaw('booking_id, SUM(amount) as paid_amount')
            ->whereNotNull('booking_id')
            ->whereIn('payment_status', ['paid', 'completed'])
            ->groupBy('booking_id');

        $balance = Booking::query()
            ->leftJoinSub($paidPayments, 'reception_paid_payments', function (JoinClause $join): void {
                $join->on('reception_paid_payments.booking_id', '=', 'bookings.id');
            })
            ->whereBetween('bookings.check_in', [$from, $until])
            ->whereIn('bookings.status', ['pending', 'confirmed'])
            ->selectRaw(<<<'SQL'
                COALESCE(SUM(
                    CASE
                        WHEN bookings.total_price > COALESCE(reception_paid_payments.paid_amount, 0)
                            THEN bookings.total_price - COALESCE(reception_paid_payments.paid_amount, 0)
                        ELSE 0
                    END
                ), 0) as outstanding_balance
                SQL)
            ->toBase()
            ->first();

        return (float) ($balance?->outstanding_balance ?? 0);
    }
}
