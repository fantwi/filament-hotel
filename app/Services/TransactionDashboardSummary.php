<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
    public function summarize(CarbonInterface $start, CarbonInterface $end, bool $includeCounts = true): array
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
                $includeCounts,
            ),
            $this->summarizeChannel(
                'Conference bookings',
                ConferenceBooking::query(),
                'total_price',
                'conference_booking_id',
                ['cancelled', 'no_show'],
                $start,
                $end,
                $includeCounts,
            ),
            $this->summarizeChannel(
                'Table reservations',
                RestaurantReservation::query(),
                'reservation_fee',
                'restaurant_reservation_id',
                ['cancelled', 'no_show'],
                $start,
                $end,
                $includeCounts,
            ),
            $this->summarizeChannel(
                'Food orders',
                RestaurantOrder::query(),
                'total',
                'restaurant_order_id',
                ['cancelled'],
                $start,
                $end,
                $includeCounts,
            ),
        ];
        $rowCollection = collect($rows);

        $totals = [
            'transactions' => $rowCollection->sum('transactions'),
            'gross' => $rowCollection->sum('gross'),
            'payments' => $rowCollection->sum('payments'),
        ];

        if ($includeCounts) {
            $totals['payment_count'] = $rowCollection->sum('payment_count');
        }

        $totals['outstanding'] = $rowCollection->sum('outstanding');

        if ($includeCounts) {
            $totals['outstanding_count'] = $rowCollection->sum('outstanding_count');
        }

        $totals['corporate_outstanding'] = $rowCollection->sum('corporate_outstanding');

        if ($includeCounts) {
            $totals['corporate_outstanding_count'] = $rowCollection->sum('corporate_outstanding_count');
        }

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
        bool $includeCounts,
    ): array {
        $transactionTable = $transactions->getModel()->getTable();
        $paidPaymentsAlias = $transactionTable.'_paid_payments';
        $paidPayments = Payment::query()
            ->select($paymentForeignKey)
            ->selectRaw('SUM(amount) as paid_amount')
            ->whereIn('payment_status', ['paid', 'completed'])
            ->whereNotNull($paymentForeignKey)
            ->groupBy($paymentForeignKey);
        $transactionsInRange = $transactions->whereBetween('created_at', [$start, $end]);
        $activeTransactions = (clone $transactionsInRange)
            ->whereNotIn('status', $excludedStatuses);
        $paymentsInRange = Payment::query()
            ->whereBetween('created_at', [$start, $end])
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

        $summary = [
            'label' => $label,
            'transactions' => (clone $transactionsInRange)->count(),
            'gross' => (float) (clone $activeTransactions)->sum($amountColumn),
            'payments' => (float) (clone $paymentsInRange)->sum('amount'),
        ];

        if ($includeCounts) {
            $summary['payment_count'] = (clone $paymentsInRange)->count();
        }

        $summary['outstanding'] = (float) (clone $outstandingTransactions)->sum(DB::raw($remainingBalance));

        if ($includeCounts) {
            $summary['outstanding_count'] = (clone $outstandingTransactions)->count();
        }

        $summary['corporate_outstanding'] = (float) (clone $outstandingTransactions)
            ->whereNotNull("{$transactionTable}.corporate_organization_id")
            ->sum(DB::raw($remainingBalance));

        if ($includeCounts) {
            $summary['corporate_outstanding_count'] = (clone $outstandingTransactions)
                ->whereNotNull("{$transactionTable}.corporate_organization_id")
                ->count();
        }

        return $summary;
    }
}
