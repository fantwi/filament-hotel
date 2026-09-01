<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\InteractsWithReportPeriod;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Filament\Pages\Page;

/**
 * Provides the revenue report Filament administration page.
 */
class RevenueReport extends Page
{
    use InteractsWithReportPeriod;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Revenue Report';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.admin.pages.revenue-report';

    /**
     * Configures report for the Filament administration interface.
     */
    public function report(): array
    {
        $paidPayments = $this->forReportPeriod(
            Payment::query()->whereIn('payment_status', ['paid', 'completed']),
        );
        $refunds = $this->forReportPeriod(
            Payment::query()->whereIn('payment_status', ['refunded', 'refund']),
        );

        $outstanding = [
            'hotel' => $this->forReportPeriod(Booking::query())
                ->whereIn('payment_status', ['pending', 'unpaid'])
                ->whereNotIn('status', ['cancelled', 'expired', 'no_show'])
                ->sum('total_price'),
            'conference' => $this->forReportPeriod(ConferenceBooking::query())
                ->whereIn('payment_status', ['pending', 'unpaid'])
                ->where('status', '!=', 'cancelled')
                ->sum('total_price'),
            'table' => $this->forReportPeriod(RestaurantReservation::query())
                ->whereIn('payment_status', ['pending', 'unpaid'])
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->sum('reservation_fee'),
            'food' => $this->forReportPeriod(RestaurantOrder::query())
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
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }
}
