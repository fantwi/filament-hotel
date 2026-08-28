<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use App\Services\CorporateCreditService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides receivables by transaction channel for the accountant dashboard.
 */
class AccountantReceivablesStats extends StatsOverviewWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view accountant dashboard') ?? false;
    }

    /**
     * Builds date-filtered receivables stats for each transaction channel.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        [$start, $end] = $this->dashboardDateRange();
        $periodLabel = $this->dashboardDateRangeLabel();

        $hotel = $this->forDashboardDateRange(Booking::query())
            ->whereIn('payment_status', ['pending', 'unpaid'])
            ->whereNotIn('status', ['cancelled', 'expired', 'no_show'])
            ->sum('total_price');
        $conference = $this->forDashboardDateRange(ConferenceBooking::query())
            ->whereIn('payment_status', ['pending', 'unpaid'])
            ->whereNotIn('status', ['cancelled', 'expired', 'no_show'])
            ->sum('total_price');
        $tables = $this->forDashboardDateRange(RestaurantReservation::query())
            ->whereIn('payment_status', ['pending', 'unpaid'])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->sum('reservation_fee');
        $food = $this->forDashboardDateRange(RestaurantOrder::query())
            ->whereIn('payment_status', ['pending', 'unpaid'])
            ->where('status', '!=', 'cancelled')
            ->sum('total');
        $corporate = app(CorporateCreditService::class)->dashboardOverview($start, $end)['outstanding'];

        return [
            Stat::make('Hotel booking receivables', $this->formatAmount($hotel))
                ->description($periodLabel)
                ->icon('heroicon-o-home-modern')
                ->color('warning'),
            Stat::make('Conference receivables', $this->formatAmount($conference))
                ->description($periodLabel)
                ->icon('heroicon-o-building-office')
                ->color('warning'),
            Stat::make('Table-reservation receivables', $this->formatAmount($tables))
                ->description($periodLabel)
                ->icon('heroicon-o-calendar-days')
                ->color('warning'),
            Stat::make('Food-order receivables', $this->formatAmount($food))
                ->description($periodLabel)
                ->icon('heroicon-o-shopping-bag')
                ->color('warning'),
            Stat::make('Corporate-billed receivables', $this->formatAmount($corporate))
                ->description('Included in channel totals')
                ->icon('heroicon-o-building-office-2')
                ->color('danger'),
        ];
    }

    /**
     * Formats a receivable amount using the application's display currency.
     */
    private function formatAmount(float|int|string|null $amount): string
    {
        return 'GHS '.number_format((float) $amount, 2);
    }
}
