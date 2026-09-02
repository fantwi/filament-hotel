<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/**
 * Provides receivables by transaction channel for the accountant dashboard.
 */
class AccountantReceivablesStats extends StatsOverviewWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

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
        $periodLabel = $this->dashboardDateRangeLabel();

        $hotel = $this->receivableTotals(
            $this->forDashboardDateRange(Booking::query())
                ->whereIn('payment_status', ['pending', 'unpaid'])
                ->whereNotIn('status', ['cancelled', 'expired', 'no_show']),
            'total_price',
        );
        $conference = $this->receivableTotals(
            $this->forDashboardDateRange(ConferenceBooking::query())
                ->whereIn('payment_status', ['pending', 'unpaid'])
                ->whereNotIn('status', ['cancelled', 'expired', 'no_show']),
            'total_price',
        );
        $tables = $this->receivableTotals(
            $this->forDashboardDateRange(RestaurantReservation::query())
                ->whereIn('payment_status', ['pending', 'unpaid'])
                ->whereNotIn('status', ['cancelled', 'no_show']),
            'reservation_fee',
        );
        $food = $this->receivableTotals(
            $this->forDashboardDateRange(RestaurantOrder::query())
                ->whereIn('payment_status', ['pending', 'unpaid'])
                ->where('status', '!=', 'cancelled'),
            'total',
        );
        $corporate = $hotel['corporate'] + $conference['corporate'] + $tables['corporate'] + $food['corporate'];

        return [
            Stat::make('Hotel booking receivables', $this->formatAmount($hotel['total']))
                ->description($periodLabel)
                ->icon('heroicon-o-home-modern')
                ->color('primary'),
            Stat::make('Conference receivables', $this->formatAmount($conference['total']))
                ->description($periodLabel)
                ->icon('heroicon-o-building-office')
                ->color('info'),
            Stat::make('Table-reservation receivables', $this->formatAmount($tables['total']))
                ->description($periodLabel)
                ->icon('heroicon-o-calendar-days')
                ->color('warning'),
            Stat::make('Food-order receivables', $this->formatAmount($food['total']))
                ->description($periodLabel)
                ->icon('heroicon-o-shopping-bag')
                ->color('success'),
            Stat::make('Corporate-billed receivables', $this->formatAmount($corporate))
                ->description('Included in channel totals')
                ->icon('heroicon-o-building-office-2')
                ->color('danger'),
        ];
    }

    /**
     * Returns total and corporate receivables from a single aggregate query.
     *
     * @return array{total: float, corporate: float}
     */
    private function receivableTotals(Builder $query, string $amountColumn): array
    {
        $totals = $query
            ->selectRaw("COALESCE(SUM({$amountColumn}), 0) as total_receivables")
            ->selectRaw("COALESCE(SUM(CASE WHEN corporate_organization_id IS NOT NULL THEN {$amountColumn} ELSE 0 END), 0) as corporate_receivables")
            ->first();

        return [
            'total' => (float) ($totals?->total_receivables ?? 0),
            'corporate' => (float) ($totals?->corporate_receivables ?? 0),
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
