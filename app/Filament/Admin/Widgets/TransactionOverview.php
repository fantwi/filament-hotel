<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class TransactionOverview extends Widget
{
    use InteractsWithDashboardDateRange;

    protected string $view = 'filament.admin.widgets.transaction-overview';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    protected function getViewData(): array
    {
        $rows = [
            $this->summary(
                'Hotel bookings',
                Booking::query(),
                'total_price',
                'booking_id',
                ['cancelled', 'expired', 'no_show'],
            ),
            $this->summary(
                'Conference bookings',
                ConferenceBooking::query(),
                'total_price',
                'conference_booking_id',
                ['cancelled'],
            ),
            $this->summary(
                'Table reservations',
                RestaurantReservation::query(),
                'reservation_fee',
                'restaurant_reservation_id',
                ['cancelled', 'no_show'],
            ),
            $this->summary(
                'Food orders',
                RestaurantOrder::query(),
                'total',
                'restaurant_order_id',
                ['cancelled'],
            ),
        ];

        return [
            'rows' => $rows,
            'periodLabel' => $this->dashboardDateRangeLabel(),
            'totals' => [
                'transactions' => collect($rows)->sum('transactions'),
                'gross' => collect($rows)->sum('gross'),
                'payments' => collect($rows)->sum('payments'),
                'payment_count' => collect($rows)->sum('payment_count'),
                'outstanding' => collect($rows)->sum('outstanding'),
                'outstanding_count' => collect($rows)->sum('outstanding_count'),
            ],
        ];
    }

    /**
     * @param  array<int, string>  $excludedStatuses
     * @return array<string, int|float|string>
     */
    private function summary(
        string $label,
        Builder $transactions,
        string $amountColumn,
        string $paymentForeignKey,
        array $excludedStatuses,
    ): array {
        $transactionsInRange = $this->forDashboardDateRange($transactions);
        $activeTransactions = (clone $transactionsInRange)
            ->whereNotIn('status', $excludedStatuses);
        $paymentsInRange = $this->forDashboardDateRange(Payment::query())
            ->whereNotNull($paymentForeignKey)
            ->whereIn('payment_status', ['paid', 'completed']);

        return [
            'label' => $label,
            'transactions' => (clone $transactionsInRange)->count(),
            'gross' => (float) (clone $activeTransactions)->sum($amountColumn),
            'payments' => (float) (clone $paymentsInRange)->sum('amount'),
            'payment_count' => (clone $paymentsInRange)->count(),
            'outstanding' => (float) (clone $activeTransactions)
                ->whereIn('payment_status', ['pending', 'unpaid'])
                ->sum($amountColumn),
            'outstanding_count' => (clone $activeTransactions)
                ->whereIn('payment_status', ['pending', 'unpaid'])
                ->count(),
        ];
    }
}
