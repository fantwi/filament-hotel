<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Calculates the transaction dashboard's channel rows and combined totals.
 */
class TransactionDashboardSummary
{
    /**
     * Summarizes every transaction channel for the supplied reporting period.
     *
     * @return array{
     *     rows: array<int, array<string, int|float|string>>,
     *     totals: array<string, int|float>
     * }
     */
    public function summarize(CarbonInterface $start, CarbonInterface $end): array
    {
        $rows = [
            $this->summarizeChannel(
                'Hotel bookings',
                Booking::query(),
                'total_price',
                'booking_id',
                ['cancelled', 'expired', 'no_show'],
                $start,
                $end,
            ),
            $this->summarizeChannel(
                'Conference bookings',
                ConferenceBooking::query(),
                'total_price',
                'conference_booking_id',
                ['cancelled', 'no_show'],
                $start,
                $end,
            ),
            $this->summarizeChannel(
                'Table reservations',
                RestaurantReservation::query(),
                'reservation_fee',
                'restaurant_reservation_id',
                ['cancelled', 'no_show'],
                $start,
                $end,
            ),
            $this->summarizeChannel(
                'Food orders',
                RestaurantOrder::query(),
                'total',
                'restaurant_order_id',
                ['cancelled'],
                $start,
                $end,
            ),
        ];
        $rowCollection = collect($rows);

        $totals = [
            'transactions' => $rowCollection->sum('transactions'),
            'gross' => $rowCollection->sum('gross'),
            'payments' => $rowCollection->sum('payments'),
            'payment_count' => $rowCollection->sum('payment_count'),
            'outstanding' => $rowCollection->sum('outstanding'),
            'outstanding_count' => $rowCollection->sum('outstanding_count'),
            'corporate_outstanding' => $rowCollection->sum('corporate_outstanding'),
            'corporate_outstanding_count' => $rowCollection->sum('corporate_outstanding_count'),
        ];

        return [
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    /**
     * Calculates one transaction channel and its linked payment activity.
     *
     * @param  array<int, string>  $excludedStatuses
     * @return array<string, int|float|string>
     */
    private function summarizeChannel(
        string $label,
        Builder $transactions,
        string $amountColumn,
        string $paymentForeignKey,
        array $excludedStatuses,
        CarbonInterface $start,
        CarbonInterface $end,
    ): array {
        $transactionTable = $transactions->getModel()->getTable();
        $paidPaymentsAlias = $transactionTable.'_paid_payments';
        $paidPayments = Payment::query()
            ->select($paymentForeignKey)
            ->selectRaw('SUM(amount) as paid_amount')
            ->whereIn('payment_status', ['paid', 'completed'])
            ->whereNotNull($paymentForeignKey)
            ->groupBy($paymentForeignKey);
        $remainingBalance = "{$transactionTable}.{$amountColumn} - COALESCE({$paidPaymentsAlias}.paid_amount, 0)";
        $excludedStatusPlaceholders = implode(', ', array_fill(0, count($excludedStatuses), '?'));
        $settledPaymentStatuses = ['paid', 'completed', 'refunded'];
        $settledStatusPlaceholders = implode(', ', array_fill(0, count($settledPaymentStatuses), '?'));
        $activeCondition = "{$transactionTable}.status NOT IN ({$excludedStatusPlaceholders})";
        $outstandingCondition = "{$activeCondition} AND {$transactionTable}.payment_status NOT IN ({$settledStatusPlaceholders}) AND {$remainingBalance} > 0";
        $corporateOutstandingCondition = "{$outstandingCondition} AND {$transactionTable}.corporate_organization_id IS NOT NULL";
        $outstandingBindings = [...$excludedStatuses, ...$settledPaymentStatuses];
        $transactionSummary = $transactions
            ->whereBetween("{$transactionTable}.created_at", [$start, $end])
            ->leftJoinSub(
                $paidPayments,
                $paidPaymentsAlias,
                "{$paidPaymentsAlias}.{$paymentForeignKey}",
                '=',
                "{$transactionTable}.id",
            )
            ->selectRaw("COUNT({$transactionTable}.id) as transactions")
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$activeCondition} THEN {$transactionTable}.{$amountColumn} ELSE 0 END), 0) as gross",
                $excludedStatuses,
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$outstandingCondition} THEN {$remainingBalance} ELSE 0 END), 0) as outstanding",
                $outstandingBindings,
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$outstandingCondition} THEN 1 ELSE 0 END), 0) as outstanding_count",
                $outstandingBindings,
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$corporateOutstandingCondition} THEN {$remainingBalance} ELSE 0 END), 0) as corporate_outstanding",
                $outstandingBindings,
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$corporateOutstandingCondition} THEN 1 ELSE 0 END), 0) as corporate_outstanding_count",
                $outstandingBindings,
            )
            ->first();
        $paymentSummary = Payment::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull($paymentForeignKey)
            ->whereIn('payment_status', ['paid', 'completed'])
            ->selectRaw('COALESCE(SUM(amount), 0) as payments')
            ->selectRaw('COUNT(*) as payment_count')
            ->first();

        return [
            'label' => $label,
            'transactions' => (int) ($transactionSummary?->transactions ?? 0),
            'gross' => (float) ($transactionSummary?->gross ?? 0),
            'payments' => (float) ($paymentSummary?->payments ?? 0),
            'payment_count' => (int) ($paymentSummary?->payment_count ?? 0),
            'outstanding' => (float) ($transactionSummary?->outstanding ?? 0),
            'outstanding_count' => (int) ($transactionSummary?->outstanding_count ?? 0),
            'corporate_outstanding' => (float) ($transactionSummary?->corporate_outstanding ?? 0),
            'corporate_outstanding_count' => (int) ($transactionSummary?->corporate_outstanding_count ?? 0),
        ];
    }
}
