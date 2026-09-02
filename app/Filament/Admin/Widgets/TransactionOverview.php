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
use Illuminate\Support\Facades\DB;

/**
 * Provides the transaction overview Filament dashboard widget.
 */
class TransactionOverview extends Widget
{
    use InteractsWithDashboardDateRange;

    protected string $view = 'filament.admin.widgets.transaction-overview';

    protected int|string|array $columnSpan = 'full';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    /**
     * Builds and returns view data.
     */
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
                ['cancelled', 'no_show'],
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
                'corporate_outstanding' => collect($rows)->sum('corporate_outstanding'),
                'corporate_outstanding_count' => collect($rows)->sum('corporate_outstanding_count'),
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
        $transactionTable = $transactions->getModel()->getTable();
        $paidPaymentsAlias = $transactionTable.'_paid_payments';
        $paidPayments = Payment::query()
            ->select($paymentForeignKey)
            ->selectRaw('SUM(amount) as paid_amount')
            ->whereIn('payment_status', ['paid', 'completed'])
            ->whereNotNull($paymentForeignKey)
            ->groupBy($paymentForeignKey);
        $transactionsInRange = $this->forDashboardDateRange($transactions);
        $activeTransactions = (clone $transactionsInRange)
            ->whereNotIn('status', $excludedStatuses);
        $paymentsInRange = $this->forDashboardDateRange(Payment::query())
            ->whereNotNull($paymentForeignKey)
            ->whereIn('payment_status', ['paid', 'completed']);
        $remainingBalance = "{$transactionTable}.{$amountColumn} - COALESCE({$paidPaymentsAlias}.paid_amount, 0)";
        $outstandingTransactions = (clone $activeTransactions)
            ->whereNotIn("{$transactionTable}.payment_status", ['paid', 'completed', 'refunded'])
            ->leftJoinSub(
                $paidPayments,
                $paidPaymentsAlias,
                "{$paidPaymentsAlias}.{$paymentForeignKey}",
                '=',
                "{$transactionTable}.id",
            )
            ->whereRaw("{$remainingBalance} > 0");

        return [
            'label' => $label,
            'transactions' => (clone $transactionsInRange)->count(),
            'gross' => (float) (clone $activeTransactions)->sum($amountColumn),
            'payments' => (float) (clone $paymentsInRange)->sum('amount'),
            'payment_count' => (clone $paymentsInRange)->count(),
            'outstanding' => (float) (clone $outstandingTransactions)->sum(DB::raw($remainingBalance)),
            'outstanding_count' => (clone $outstandingTransactions)->count(),
            'corporate_outstanding' => (float) (clone $outstandingTransactions)
                ->whereNotNull("{$transactionTable}.corporate_organization_id")
                ->sum(DB::raw($remainingBalance)),
            'corporate_outstanding_count' => (clone $outstandingTransactions)
                ->whereNotNull("{$transactionTable}.corporate_organization_id")
                ->count(),
        ];
    }
}
