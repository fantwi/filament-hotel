<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\InteractsWithReportPeriod;
use App\Filament\Admin\Pages\Dashboards\TransactionDashboard;
use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use App\Services\PaymentReportFilters;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
        [$periodStart, $periodEnd] = $this->periodBounds();
        $paidPayments = Payment::query()
            ->whereIn('payment_status', ['paid', 'completed', 'refunded', 'refund'])
            ->whereBetween('created_at', [$periodStart, $periodEnd]);
        $refunds = Payment::query()
            ->whereNotNull('refunded_at')
            ->whereBetween('refunded_at', [$periodStart, $periodEnd]);

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
        $revenue = (float) $methods->sum('total');
        $paymentsReceived = (int) $methods->sum('payment_count');
        $revenueByChannel = collect(array_keys(self::REVENUE_CHANNEL_CONDITIONS))
            ->mapWithKeys(fn (string $channel): array => [
                $channel => [
                    'total' => (float) $methods->sum("{$channel}_total"),
                    'payment_count' => (int) $methods->sum("{$channel}_payment_count"),
                ],
            ])
            ->all();
        $granularity = $this->trendGranularity($periodStart, $periodEnd);
        $collectedTrend = $this->paymentAggregates($paidPayments, 'created_at', $granularity);
        $refundTrend = $this->paymentAggregates($refunds, 'refunded_at', $granularity);
        $refundTotal = (float) $refundTrend->sum('total');
        $refundCount = (int) $refundTrend->sum('payment_count');
        $netRevenue = $revenue - $refundTotal;
        [$previousStart, $previousEnd] = $this->previousPeriodBounds($periodStart, $periodEnd);
        $previousRevenue = (float) Payment::query()
            ->whereIn('payment_status', ['paid', 'completed', 'refunded', 'refund'])
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('amount');
        $previousRefunds = (float) Payment::query()
            ->whereNotNull('refunded_at')
            ->whereBetween('refunded_at', [$previousStart, $previousEnd])
            ->sum('amount');
        $previousNetRevenue = $previousRevenue - $previousRefunds;

        return [
            'revenue' => $revenue,
            'refunds' => $refundTotal,
            'netRevenue' => $netRevenue,
            'outstanding' => array_sum($outstanding),
            'outstandingBreakdown' => $outstanding,
            'paymentsReceived' => $paymentsReceived,
            'refundCount' => $refundCount,
            'revenueByChannel' => $revenueByChannel,
            'methods' => $methods,
            'comparison' => [
                'previousPeriodLabel' => $this->dateRangeLabel($previousStart, $previousEnd),
                'revenue' => $this->comparisonMetric($revenue, $previousRevenue),
                'refunds' => $this->comparisonMetric($refundTotal, $previousRefunds),
                'netRevenue' => $this->comparisonMetric($netRevenue, $previousNetRevenue),
            ],
            'trend' => $this->buildTrend(
                $periodStart,
                $periodEnd,
                $granularity,
                $collectedTrend,
                $refundTrend,
            ),
        ];
    }

    /**
     * Builds permission-aware destinations for every actionable report metric.
     *
     * @param  list<string>  $paymentMethods
     * @return array{
     *     revenue: string,
     *     refunds: string,
     *     netRevenue: string,
     *     outstanding: string,
     *     channels: array<string, string>,
     *     methods: array<string, string>
     * }
     */
    public function drillDownUrls(array $paymentMethods): array
    {
        $channels = [
            'hotel' => 'hotel_bookings',
            'conference' => 'conference_bookings',
            'table' => 'table_reservations',
            'food' => 'food_orders',
            'other' => 'other',
        ];

        return [
            'revenue' => $this->paymentOrTransactionUrl('revenue'),
            'refunds' => $this->paymentOrTransactionUrl('refunded', dateBasis: 'refunded_at'),
            'netRevenue' => $this->transactionDashboardUrl('collection-performance'),
            'outstanding' => $this->transactionDashboardUrl('transaction-breakdown'),
            'channels' => collect($channels)
                ->map(fn (string $type): string => $this->paymentOrTransactionUrl(
                    'revenue',
                    transactionType: $type,
                    fallbackSection: 'transaction-breakdown',
                ))
                ->all(),
            'methods' => collect($paymentMethods)
                ->filter(fn (string $method): bool => array_key_exists($method, PaymentReportFilters::methodOptions()))
                ->mapWithKeys(fn (string $method): array => [
                    $method => $this->paymentOrTransactionUrl('revenue', paymentMethod: $method),
                ])
                ->all(),
        ];
    }

    /**
     * Links authorized finance roles to payments and other roles to safe analysis.
     */
    private function paymentOrTransactionUrl(
        string $status,
        string $transactionType = 'all',
        string $paymentMethod = 'all',
        string $dateBasis = 'created_at',
        string $fallbackSection = 'collection-performance',
    ): string {
        if (! (auth()->check() && PaymentResource::canViewAny())) {
            return $this->transactionDashboardUrl($fallbackSection);
        }

        [$start, $end] = $this->periodBounds();
        $period = array_key_exists($this->period, PaymentReportFilters::periodOptions())
            ? $this->period
            : 'monthly';

        return PaymentResource::getUrl('index', [
            'filters' => [
                'transaction_type' => $transactionType,
                'payment_status' => $status,
                'payment_method' => $paymentMethod,
                'date_basis' => $dateBasis,
                'period' => $period,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
        ]);
    }

    /**
     * Opens the matching transaction analysis, falling back to this report safely.
     */
    private function transactionDashboardUrl(string $section): string
    {
        [$start, $end] = $this->periodBounds();
        $period = array_key_exists($this->period, PaymentReportFilters::periodOptions())
            ? $this->period
            : 'monthly';

        if (auth()->check() && TransactionDashboard::canAccess()) {
            return TransactionDashboard::getUrl([
                'filters' => [
                    'period' => $period,
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ],
            ]).'#'.ltrim($section, '#');
        }

        return static::getUrl([
            'period' => $this->period,
            'startDate' => $start->toDateString(),
            'endDate' => $end->toDateString(),
        ]).'#'.ltrim($section, '#');
    }

    /**
     * Returns the immediately preceding inclusive period with the same number of days.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function previousPeriodBounds(Carbon $periodStart, Carbon $periodEnd): array
    {
        $days = (int) $periodStart->copy()->startOfDay()
            ->diffInDays($periodEnd->copy()->startOfDay()) + 1;
        $previousEnd = $periodStart->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($days - 1)->startOfDay();

        return [$previousStart, $previousEnd];
    }

    /**
     * Selects a readable chart resolution without producing an excessive bucket count.
     */
    private function trendGranularity(Carbon $periodStart, Carbon $periodEnd): string
    {
        $days = (int) $periodStart->copy()->startOfDay()
            ->diffInDays($periodEnd->copy()->startOfDay()) + 1;

        return match (true) {
            $days === 1 => 'hour',
            $days <= 45 => 'day',
            $days <= 730 => 'month',
            default => 'year',
        };
    }

    /**
     * Aggregates payment amounts and counts into database-portable time buckets.
     *
     * @return Collection<string, object>
     */
    private function paymentAggregates(Builder $payments, string $column, string $granularity): Collection
    {
        $expression = $this->bucketExpression($column, $granularity);

        return (clone $payments)
            ->selectRaw("{$expression} as bucket")
            ->selectRaw('SUM(amount) as total, COUNT(*) as payment_count')
            ->groupByRaw($expression)
            ->get()
            ->keyBy(fn (object $aggregate): string => (string) $aggregate->bucket);
    }

    /**
     * Builds every selected-period chart bucket, including dates without activity.
     *
     * @param  Collection<string, object>  $collectedTrend
     * @param  Collection<string, object>  $refundTrend
     * @return array{granularity: string, labels: list<string>, collected: list<float>, refunds: list<float>, net: list<float>}
     */
    private function buildTrend(
        Carbon $periodStart,
        Carbon $periodEnd,
        string $granularity,
        Collection $collectedTrend,
        Collection $refundTrend,
    ): array {
        $cursor = match ($granularity) {
            'hour' => $periodStart->copy()->startOfHour(),
            'day' => $periodStart->copy()->startOfDay(),
            'month' => $periodStart->copy()->startOfMonth(),
            default => $periodStart->copy()->startOfYear(),
        };
        $labels = [];
        $collected = [];
        $refunds = [];

        while ($cursor->lessThanOrEqualTo($periodEnd)) {
            $key = $this->bucketKey($cursor, $granularity);
            $collectedAmount = (float) ($collectedTrend->get($key)?->total ?? 0);
            $refundAmount = (float) ($refundTrend->get($key)?->total ?? 0);

            $labels[] = match ($granularity) {
                'hour' => $cursor->format('g A'),
                'day' => $cursor->format('M j'),
                'month' => $cursor->format('M Y'),
                default => $cursor->format('Y'),
            };
            $collected[] = $collectedAmount;
            $refunds[] = $refundAmount;

            match ($granularity) {
                'hour' => $cursor->addHour(),
                'day' => $cursor->addDay(),
                'month' => $cursor->addMonth(),
                default => $cursor->addYear(),
            };
        }

        return [
            'granularity' => $granularity,
            'labels' => $labels,
            'collected' => $collected,
            'refunds' => $refunds,
            'net' => array_map(
                fn (float $amount, int $index): float => $amount - $refunds[$index],
                $collected,
                array_keys($collected),
            ),
        ];
    }

    /**
     * Returns a database-specific expression for a supported timestamp bucket.
     */
    private function bucketExpression(string $column, string $granularity): string
    {
        $driver = Payment::query()->getModel()->getConnection()->getDriverName();

        return match ($driver) {
            'sqlite' => match ($granularity) {
                'hour' => "strftime('%Y-%m-%d %H:00:00', {$column})",
                'day' => "strftime('%Y-%m-%d', {$column})",
                'month' => "strftime('%Y-%m-01', {$column})",
                default => "strftime('%Y-01-01', {$column})",
            },
            'pgsql' => match ($granularity) {
                'hour' => "to_char(date_trunc('hour', {$column}), 'YYYY-MM-DD HH24:00:00')",
                'day' => "to_char({$column}, 'YYYY-MM-DD')",
                'month' => "to_char({$column}, 'YYYY-MM-01')",
                default => "to_char({$column}, 'YYYY-01-01')",
            },
            default => match ($granularity) {
                'hour' => "DATE_FORMAT({$column}, '%Y-%m-%d %H:00:00')",
                'day' => "DATE_FORMAT({$column}, '%Y-%m-%d')",
                'month' => "DATE_FORMAT({$column}, '%Y-%m-01')",
                default => "DATE_FORMAT({$column}, '%Y-01-01')",
            },
        };
    }

    /**
     * Formats a chart cursor to the key returned by its database aggregate.
     */
    private function bucketKey(Carbon $cursor, string $granularity): string
    {
        return match ($granularity) {
            'hour' => $cursor->format('Y-m-d H:00:00'),
            'day' => $cursor->format('Y-m-d'),
            'month' => $cursor->format('Y-m-01'),
            default => $cursor->format('Y-01-01'),
        };
    }

    /**
     * Produces comparable current, previous, absolute, and percentage values.
     *
     * @return array{current: float, previous: float, difference: float, percentageChange: float|null}
     */
    private function comparisonMetric(float $current, float $previous): array
    {
        $difference = $current - $previous;

        return [
            'current' => $current,
            'previous' => $previous,
            'difference' => $difference,
            'percentageChange' => $previous === 0.0
                ? null
                : round(($difference / abs($previous)) * 100, 1),
        ];
    }

    /**
     * Formats an inclusive period for the comparison widget.
     */
    private function dateRangeLabel(Carbon $start, Carbon $end): string
    {
        return $start->isSameDay($end)
            ? $start->format('M j, Y')
            : $start->format('M j, Y').' to '.$end->format('M j, Y');
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
