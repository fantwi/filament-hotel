<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\InteractsWithReportPeriod;
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
    use InteractsWithReportPeriod;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Revenue Report';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.admin.pages.revenue-report';

    /**
     * Mutually exclusive payment classifications, ordered by transaction priority.
     *
     * @var array<string, string>
     */
    private const REVENUE_CHANNEL_CONDITIONS = [
        'hotel' => 'booking_id IS NOT NULL',
        'conference' => 'booking_id IS NULL AND conference_booking_id IS NOT NULL',
        'table' => 'booking_id IS NULL AND conference_booking_id IS NULL AND restaurant_reservation_id IS NOT NULL',
        'food' => 'booking_id IS NULL AND conference_booking_id IS NULL AND restaurant_reservation_id IS NULL AND restaurant_order_id IS NOT NULL',
        'other' => 'booking_id IS NULL AND conference_booking_id IS NULL AND restaurant_reservation_id IS NULL AND restaurant_order_id IS NULL',
    ];

    /**
     * Configures report for the Filament administration interface.
     */
    public function report(): array
    {
        $paidPayments = $this->forReportPeriod(
            Payment::query()->whereIn('payment_status', ['paid', 'completed', 'refunded', 'refund']),
        );
        $refunds = $this->forReportPeriod(
            Payment::query()->whereNotNull('refunded_at'),
            'refunded_at',
        );

        $outstanding = [
            'hotel' => $this->outstandingBalance(
                Booking::query(),
                'total_price',
                'booking_id',
                ['cancelled', 'expired', 'no_show'],
            ),
            'conference' => $this->outstandingBalance(
                ConferenceBooking::query(),
                'total_price',
                'conference_booking_id',
                ['cancelled', 'no_show'],
            ),
            'table' => $this->outstandingBalance(
                RestaurantReservation::query(),
                'reservation_fee',
                'restaurant_reservation_id',
                ['cancelled', 'no_show'],
            ),
            'food' => $this->outstandingBalance(
                RestaurantOrder::query(),
                'total',
                'restaurant_order_id',
                ['cancelled'],
            ),
        ];

        $revenue = (float) (clone $paidPayments)->sum('amount');
        $refundTotal = (float) (clone $refunds)->sum('amount');
        $paymentMethodsQuery = (clone $paidPayments)
            ->selectRaw('method, SUM(amount) as total, COUNT(*) as payment_count');

        foreach (self::REVENUE_CHANNEL_CONDITIONS as $channel => $condition) {
            $paymentMethodsQuery
                ->selectRaw("SUM(CASE WHEN {$condition} THEN amount ELSE 0 END) as {$channel}_total")
                ->selectRaw("SUM(CASE WHEN {$condition} THEN 1 ELSE 0 END) as {$channel}_payment_count");
        }

        $methods = $paymentMethodsQuery
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();
        $revenueByChannel = collect(array_keys(self::REVENUE_CHANNEL_CONDITIONS))
            ->mapWithKeys(fn (string $channel): array => [
                $channel => [
                    'total' => (float) $methods->sum("{$channel}_total"),
                    'payment_count' => (int) $methods->sum("{$channel}_payment_count"),
                ],
            ])
            ->all();

        return [
            'revenue' => $revenue,
            'refunds' => $refundTotal,
            'netRevenue' => $revenue - $refundTotal,
            'outstanding' => array_sum($outstanding),
            'outstandingBreakdown' => $outstanding,
            'paymentsReceived' => (clone $paidPayments)->count(),
            'refundCount' => (clone $refunds)->count(),
            'revenueByChannel' => $revenueByChannel,
            'methods' => $methods,
        ];
    }

    /**
     * Calculates the unpaid portion of active transactions in the report period.
     *
     * @param  array<int, string>  $excludedStatuses
     */
    private function outstandingBalance(
        Builder $transactions,
        string $amountColumn,
        string $paymentForeignKey,
        array $excludedStatuses,
    ): float {
        $transactionTable = $transactions->getModel()->getTable();
        $paidPaymentsAlias = $transactionTable.'_revenue_report_paid';
        $paidPayments = Payment::query()
            ->select($paymentForeignKey)
            ->selectRaw('SUM(amount) as paid_amount')
            ->whereIn('payment_status', ['paid', 'completed'])
            ->whereNotNull($paymentForeignKey)
            ->groupBy($paymentForeignKey);
        $remainingBalance = "{$transactionTable}.{$amountColumn} - COALESCE({$paidPaymentsAlias}.paid_amount, 0)";

        $outstanding = $this->forReportPeriod(
            $transactions,
            "{$transactionTable}.created_at",
        )
            ->leftJoinSub(
                $paidPayments,
                $paidPaymentsAlias,
                "{$paidPaymentsAlias}.{$paymentForeignKey}",
                '=',
                "{$transactionTable}.id",
            )
            ->whereNotIn("{$transactionTable}.status", $excludedStatuses)
            ->whereNotIn("{$transactionTable}.payment_status", ['paid', 'completed', 'refunded'])
            ->whereRaw("{$remainingBalance} > 0")
            ->selectRaw("COALESCE(SUM({$remainingBalance}), 0) as outstanding")
            ->value('outstanding');

        return (float) $outstanding;
    }

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }
}
