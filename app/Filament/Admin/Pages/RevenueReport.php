<?php

namespace App\Filament\Admin\Pages;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

/**
 * Provides the revenue report Filament administration page.
 */
class RevenueReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Revenue Report';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.admin.pages.revenue-report';

    public string $period = 'this_month';

    /**
     * Configures period label for the Filament administration interface.
     */
    public function periodLabel(): string
    {
        return match ($this->period) {
            'today' => 'Today',
            'this_week' => 'This week',
            'this_quarter' => 'This quarter',
            'this_year' => 'This year',
            'all' => 'All time',
            default => 'This month',
        };
    }

    /**
     * Configures report for the Filament administration interface.
     */
    public function report(): array
    {
        $paidPayments = $this->forPeriod(
            Payment::query()->whereIn('payment_status', ['paid', 'completed']),
        );
        $refunds = $this->forPeriod(
            Payment::query()->whereIn('payment_status', ['refunded', 'refund']),
        );

        $outstanding = [
            'hotel' => $this->forPeriod(Booking::query())
                ->whereIn('payment_status', ['pending', 'unpaid'])
                ->whereNotIn('status', ['cancelled', 'expired', 'no_show'])
                ->sum('total_price'),
            'conference' => $this->forPeriod(ConferenceBooking::query())
                ->whereIn('payment_status', ['pending', 'unpaid'])
                ->where('status', '!=', 'cancelled')
                ->sum('total_price'),
            'table' => $this->forPeriod(RestaurantReservation::query())
                ->whereIn('payment_status', ['pending', 'unpaid'])
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->sum('reservation_fee'),
            'food' => $this->forPeriod(RestaurantOrder::query())
                ->whereIn('payment_status', ['pending', 'unpaid'])
                ->where('status', '!=', 'cancelled')
                ->sum('total'),
        ];

        $revenue = (float) (clone $paidPayments)->sum('amount');
        $refundTotal = (float) (clone $refunds)->sum('amount');

        return [
            'revenue' => $revenue,
            'refunds' => $refundTotal,
            'netRevenue' => $revenue - $refundTotal,
            'outstanding' => array_sum($outstanding),
            'outstandingBreakdown' => $outstanding,
            'paymentsReceived' => (clone $paidPayments)->count(),
            'refundCount' => (clone $refunds)->count(),
            'methods' => (clone $paidPayments)
                ->selectRaw('method, SUM(amount) as total, COUNT(*) as payment_count')
                ->groupBy('method')
                ->orderByDesc('total')
                ->get(),
        ];
    }

    /**
     * Configures for period for the Filament administration interface.
     */
    private function forPeriod(Builder $query, string $column = 'created_at'): Builder
    {
        return match ($this->period) {
            'today' => $query->whereDate($column, today()),
            'this_week' => $query->whereBetween($column, [now()->startOfWeek(), now()->endOfWeek()]),
            'this_quarter' => $query->whereBetween($column, [now()->startOfQuarter(), now()->endOfQuarter()]),
            'this_year' => $query->whereBetween($column, [now()->startOfYear(), now()->endOfYear()]),
            'all' => $query,
            default => $query->whereBetween($column, [now()->startOfMonth(), now()->endOfMonth()]),
        };
    }

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }
}
