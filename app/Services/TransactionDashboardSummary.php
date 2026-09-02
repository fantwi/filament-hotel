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
        $gross = (float) $rowCollection->sum('gross');
        $payments = (float) $rowCollection->sum('payments');
        $refunds = (float) $rowCollection->sum('refunds');
        $cohortCollections = (float) $rowCollection->sum('cohort_collections');

        $totals = [
            'transactions' => $rowCollection->sum('transactions'),
            'gross' => $gross,
            'payments' => $payments,
            'payment_count' => $rowCollection->sum('payment_count'),
            'refunds' => $refunds,
            'refund_count' => $rowCollection->sum('refund_count'),
            'net_collections' => $payments - $refunds,
            'cohort_collections' => $cohortCollections,
            'collection_rate' => $gross > 0 ? round(($cohortCollections / $gross) * 100, 2) : 0.0,
            'outstanding' => $rowCollection->sum('outstanding'),
            'outstanding_count' => $rowCollection->sum('outstanding_count'),
            'non_corporate_outstanding' => $rowCollection->sum('non_corporate_outstanding'),
            'non_corporate_outstanding_count' => $rowCollection->sum('non_corporate_outstanding_count'),
            'corporate_outstanding' => $rowCollection->sum('corporate_outstanding'),
            'corporate_outstanding_count' => $rowCollection->sum('corporate_outstanding_count'),
            'overdue_corporate_outstanding' => $rowCollection->sum('overdue_corporate_outstanding'),
            'overdue_corporate_outstanding_count' => $rowCollection->sum('overdue_corporate_outstanding_count'),
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
        $corporateOrganizationsAlias = $transactionTable.'_corporate_organizations';
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
        $overdueCorporateCondition = $corporateOutstandingCondition.' AND '.$this->corporateDueDateExpression(
            $transactions,
            $transactionTable,
            $corporateOrganizationsAlias,
        ).' < ?';
        $collectedAmount = "CASE WHEN COALESCE({$paidPaymentsAlias}.paid_amount, 0) > {$transactionTable}.{$amountColumn} THEN {$transactionTable}.{$amountColumn} ELSE COALESCE({$paidPaymentsAlias}.paid_amount, 0) END";
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
            ->leftJoin(
                "corporate_organizations as {$corporateOrganizationsAlias}",
                "{$corporateOrganizationsAlias}.id",
                '=',
                "{$transactionTable}.corporate_organization_id",
            )
            ->selectRaw("COUNT({$transactionTable}.id) as transactions")
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$activeCondition} THEN {$transactionTable}.{$amountColumn} ELSE 0 END), 0) as gross",
                $excludedStatuses,
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$activeCondition} THEN {$collectedAmount} ELSE 0 END), 0) as cohort_collections",
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
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$overdueCorporateCondition} THEN {$remainingBalance} ELSE 0 END), 0) as overdue_corporate_outstanding",
                [...$outstandingBindings, now()],
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$overdueCorporateCondition} THEN 1 ELSE 0 END), 0) as overdue_corporate_outstanding_count",
                [...$outstandingBindings, now()],
            )
            ->first();
        $paymentTable = (new Payment)->getTable();
        $paymentSummary = Payment::query()
            ->whereNotNull($paymentForeignKey)
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$paymentTable}.payment_status IN (?, ?) AND {$paymentTable}.created_at BETWEEN ? AND ? THEN {$paymentTable}.amount ELSE 0 END), 0) as payments",
                ['paid', 'completed', $start, $end],
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$paymentTable}.payment_status IN (?, ?) AND {$paymentTable}.created_at BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) as payment_count",
                ['paid', 'completed', $start, $end],
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$paymentTable}.payment_status IN (?, ?) AND {$paymentTable}.updated_at BETWEEN ? AND ? THEN {$paymentTable}.amount ELSE 0 END), 0) as refunds",
                ['refunded', 'refund', $start, $end],
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$paymentTable}.payment_status IN (?, ?) AND {$paymentTable}.updated_at BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) as refund_count",
                ['refunded', 'refund', $start, $end],
            )
            ->first();

        $outstanding = (float) ($transactionSummary?->outstanding ?? 0);
        $outstandingCount = (int) ($transactionSummary?->outstanding_count ?? 0);
        $corporateOutstanding = (float) ($transactionSummary?->corporate_outstanding ?? 0);
        $corporateOutstandingCount = (int) ($transactionSummary?->corporate_outstanding_count ?? 0);

        return [
            'label' => $label,
            'transactions' => (int) ($transactionSummary?->transactions ?? 0),
            'gross' => (float) ($transactionSummary?->gross ?? 0),
            'payments' => (float) ($paymentSummary?->payments ?? 0),
            'payment_count' => (int) ($paymentSummary?->payment_count ?? 0),
            'refunds' => (float) ($paymentSummary?->refunds ?? 0),
            'refund_count' => (int) ($paymentSummary?->refund_count ?? 0),
            'cohort_collections' => (float) ($transactionSummary?->cohort_collections ?? 0),
            'outstanding' => $outstanding,
            'outstanding_count' => $outstandingCount,
            'non_corporate_outstanding' => max(0, $outstanding - $corporateOutstanding),
            'non_corporate_outstanding_count' => max(0, $outstandingCount - $corporateOutstandingCount),
            'corporate_outstanding' => $corporateOutstanding,
            'corporate_outstanding_count' => $corporateOutstandingCount,
            'overdue_corporate_outstanding' => (float) ($transactionSummary?->overdue_corporate_outstanding ?? 0),
            'overdue_corporate_outstanding_count' => (int) ($transactionSummary?->overdue_corporate_outstanding_count ?? 0),
        ];
    }

    /**
     * Builds a database-specific expression for the end of corporate payment terms.
     */
    private function corporateDueDateExpression(
        Builder $transactions,
        string $transactionTable,
        string $corporateOrganizationsAlias,
    ): string {
        $terms = "COALESCE({$corporateOrganizationsAlias}.payment_terms_days, 30)";
        $createdAt = "{$transactionTable}.created_at";

        return match ($transactions->getConnection()->getDriverName()) {
            'sqlite' => "datetime({$createdAt}, '+' || {$terms} || ' days')",
            'pgsql' => "{$createdAt} + ({$terms} * INTERVAL '1 day')",
            'sqlsrv' => "DATEADD(day, {$terms}, {$createdAt})",
            default => "DATE_ADD({$createdAt}, INTERVAL {$terms} DAY)",
        };
    }
}
