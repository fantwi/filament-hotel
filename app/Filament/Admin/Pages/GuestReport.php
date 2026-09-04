<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\InteractsWithReportPeriod;
use App\Filament\Admin\Pages\Dashboards\TransactionDashboard;
use App\Filament\Admin\Resources\Guests\GuestResource;
use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Models\Guest;
use App\Models\Payment;
use App\Services\PaymentReportFilters;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Provides the guest report Filament administration page.
 */
class GuestReport extends Page
{
    use InteractsWithReportPeriod;

    private const RESOLVED_GUEST_ID = 'COALESCE(payments.guest_id, guest_report_bookings.guest_id, guest_report_conferences.guest_id, guest_report_reservations.guest_id, guest_report_orders.guest_id)';

    private const DISTINCT_SERVICE_VISIT_COUNT = 'COUNT(DISTINCT payments.booking_id) + COUNT(DISTINCT CASE WHEN payments.booking_id IS NULL THEN payments.conference_booking_id END) + COUNT(DISTINCT CASE WHEN payments.booking_id IS NULL AND payments.conference_booking_id IS NULL THEN payments.restaurant_reservation_id END) + COUNT(DISTINCT CASE WHEN payments.booking_id IS NULL AND payments.conference_booking_id IS NULL AND payments.restaurant_reservation_id IS NULL THEN payments.restaurant_order_id END)';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Guest Report';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.admin.pages.guest-report';

    /**
     * Configures report for the Filament administration interface.
     */
    public function report(): array
    {
        [$periodStart, $periodEnd] = $this->periodBounds();
        [$previousStart, $previousEnd] = $this->previousPeriodBounds($periodStart, $periodEnd);
        $collectedPayments = $this->collectedGuestPayments();
        $refundedPayments = $this->refundedGuestPayments();

        $guestSummary = Guest::query()
            ->selectRaw('COUNT(*) as total_guests')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN guests.created_at BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) as new_guests',
                [$periodStart, $periodEnd],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN guests.created_at BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) as previous_new_guests',
                [$previousStart, $previousEnd],
            )
            ->first();

        $collectedSummary = (clone $collectedPayments)
            ->selectRaw('COUNT(DISTINCT '.self::RESOLVED_GUEST_ID.') as paying_guests')
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as total_paid')
            ->selectRaw('COUNT(payments.id) as payment_count')
            ->selectRaw('COUNT(payments.booking_id) as hotel_payment_count')
            ->selectRaw('COUNT(payments.conference_booking_id) as conference_payment_count')
            ->selectRaw('COUNT(payments.restaurant_reservation_id) as table_payment_count')
            ->selectRaw('COUNT(payments.restaurant_order_id) as food_payment_count')
            ->first();

        $refundSummary = (clone $refundedPayments)
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as refund_total')
            ->selectRaw('COUNT(payments.id) as refund_count')
            ->first();

        $payingGuests = (int) $collectedSummary->paying_guests;
        $totalPaid = (float) $collectedSummary->total_paid;
        $refundTotal = (float) $refundSummary->refund_total;

        $topGuests = (clone $collectedPayments)
            ->with('guest')
            ->selectRaw(self::RESOLVED_GUEST_ID.' as guest_id, SUM(payments.amount) as total_spend, COUNT(*) as payment_count')
            ->groupByRaw(self::RESOLVED_GUEST_ID)
            ->orderByDesc('total_spend')
            ->limit(5)
            ->get();

        $returningGuests = (clone $collectedPayments)
            ->selectRaw(self::RESOLVED_GUEST_ID.' as guest_id')
            ->groupByRaw(self::RESOLVED_GUEST_ID)
            ->havingRaw(self::DISTINCT_SERVICE_VISIT_COUNT.' >= 2')
            ->get()
            ->count();
        $previousGuestVisits = $this->guestVisitSummary(
            $this->collectedGuestPaymentsBetween($previousStart, $previousEnd),
        );
        $granularity = $this->trendGranularity($periodStart, $periodEnd);
        $newGuestTrend = $this->newGuestTrendAggregates($periodStart, $periodEnd, $granularity);
        $payingGuestTrend = $this->payingGuestTrendAggregates($collectedPayments, $granularity);
        $returningGuestTrend = $this->returningGuestTrendAggregates($collectedPayments, $granularity);

        return [
            'totalGuests' => (int) $guestSummary->total_guests,
            'newGuests' => (int) $guestSummary->new_guests,
            'hasPeriodActivity' => (int) $guestSummary->new_guests > 0
                || (int) $collectedSummary->payment_count > 0
                || (int) $refundSummary->refund_count > 0,
            'payingGuests' => $payingGuests,
            'returningGuests' => $returningGuests,
            'averageSpend' => $payingGuests > 0 ? $totalPaid / $payingGuests : 0,
            'totalPaid' => $totalPaid,
            'refundTotal' => $refundTotal,
            'refundCount' => (int) $refundSummary->refund_count,
            'netSpend' => $totalPaid - $refundTotal,
            'paymentCount' => (int) $collectedSummary->payment_count,
            'activity' => [
                'hotel' => (int) $collectedSummary->hotel_payment_count,
                'conference' => (int) $collectedSummary->conference_payment_count,
                'table' => (int) $collectedSummary->table_payment_count,
                'food' => (int) $collectedSummary->food_payment_count,
            ],
            'topGuests' => $topGuests,
            'comparison' => [
                'previousPeriodLabel' => $this->dateRangeLabel($previousStart, $previousEnd),
                'newGuests' => $this->comparisonMetric(
                    (int) $guestSummary->new_guests,
                    (int) $guestSummary->previous_new_guests,
                ),
                'payingGuests' => $this->comparisonMetric(
                    $payingGuests,
                    $previousGuestVisits['payingGuests'],
                ),
                'returningGuests' => $this->comparisonMetric(
                    $returningGuests,
                    $previousGuestVisits['returningGuests'],
                ),
            ],
            'trend' => $this->buildTrend(
                $periodStart,
                $periodEnd,
                $granularity,
                $newGuestTrend,
                $payingGuestTrend,
                $returningGuestTrend,
            ),
        ];
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
     * Counts distinct paying and returning guests for one comparison period.
     *
     * @return array{payingGuests: int, returningGuests: int}
     */
    private function guestVisitSummary(Builder $payments): array
    {
        $visitsByGuest = (clone $payments)
            ->selectRaw(self::RESOLVED_GUEST_ID.' as guest_id')
            ->selectRaw(self::DISTINCT_SERVICE_VISIT_COUNT.' as service_visit_count')
            ->groupByRaw(self::RESOLVED_GUEST_ID);
        $summary = DB::query()
            ->fromSub($visitsByGuest, 'guest_period_visits')
            ->selectRaw('COUNT(*) as paying_guests')
            ->selectRaw('COALESCE(SUM(CASE WHEN service_visit_count >= 2 THEN 1 ELSE 0 END), 0) as returning_guests')
            ->first();

        return [
            'payingGuests' => (int) $summary->paying_guests,
            'returningGuests' => (int) $summary->returning_guests,
        ];
    }

    /**
     * Selects a readable chart resolution without producing excessive buckets.
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
     * Groups newly created guest profiles into chart buckets.
     *
     * @return Collection<string, object>
     */
    private function newGuestTrendAggregates(Carbon $periodStart, Carbon $periodEnd, string $granularity): Collection
    {
        $expression = $this->bucketExpression('guests.created_at', $granularity);

        return Guest::query()
            ->whereBetween('guests.created_at', [$periodStart, $periodEnd])
            ->selectRaw("{$expression} as bucket")
            ->selectRaw('COUNT(*) as guest_count')
            ->groupByRaw($expression)
            ->get()
            ->keyBy(fn (object $aggregate): string => (string) $aggregate->bucket);
    }

    /**
     * Groups distinct guests with collected payments into chart buckets.
     *
     * @return Collection<string, object>
     */
    private function payingGuestTrendAggregates(Builder $payments, string $granularity): Collection
    {
        $expression = $this->bucketExpression('payments.created_at', $granularity);

        return (clone $payments)
            ->selectRaw("{$expression} as bucket")
            ->selectRaw('COUNT(DISTINCT '.self::RESOLVED_GUEST_ID.') as guest_count')
            ->groupByRaw($expression)
            ->get()
            ->keyBy(fn (object $aggregate): string => (string) $aggregate->bucket);
    }

    /**
     * Groups guests with at least two distinct paid service visits per chart bucket.
     *
     * @return Collection<string, int>
     */
    private function returningGuestTrendAggregates(Builder $payments, string $granularity): Collection
    {
        $expression = $this->bucketExpression('payments.created_at', $granularity);

        return (clone $payments)
            ->selectRaw("{$expression} as bucket")
            ->selectRaw(self::RESOLVED_GUEST_ID.' as guest_id')
            ->groupByRaw($expression.', '.self::RESOLVED_GUEST_ID)
            ->havingRaw(self::DISTINCT_SERVICE_VISIT_COUNT.' >= 2')
            ->get()
            ->groupBy(fn (object $aggregate): string => (string) $aggregate->bucket)
            ->map(fn (Collection $guests): int => $guests->count());
    }

    /**
     * Builds every selected-period chart bucket, including dates without activity.
     *
     * @param  Collection<string, object>  $newGuestTrend
     * @param  Collection<string, object>  $payingGuestTrend
     * @param  Collection<string, int>  $returningGuestTrend
     * @return array{granularity: string, labels: list<string>, newGuests: list<int>, payingGuests: list<int>, returningGuests: list<int>}
     */
    private function buildTrend(
        Carbon $periodStart,
        Carbon $periodEnd,
        string $granularity,
        Collection $newGuestTrend,
        Collection $payingGuestTrend,
        Collection $returningGuestTrend,
    ): array {
        $cursor = match ($granularity) {
            'hour' => $periodStart->copy()->startOfHour(),
            'day' => $periodStart->copy()->startOfDay(),
            'month' => $periodStart->copy()->startOfMonth(),
            default => $periodStart->copy()->startOfYear(),
        };
        $labels = [];
        $newGuests = [];
        $payingGuests = [];
        $returningGuests = [];

        while ($cursor->lessThanOrEqualTo($periodEnd)) {
            $key = $this->bucketKey($cursor, $granularity);

            $labels[] = match ($granularity) {
                'hour' => $cursor->format('g A'),
                'day' => $cursor->format('M j'),
                'month' => $cursor->format('M Y'),
                default => $cursor->format('Y'),
            };
            $newGuests[] = (int) ($newGuestTrend->get($key)?->guest_count ?? 0);
            $payingGuests[] = (int) ($payingGuestTrend->get($key)?->guest_count ?? 0);
            $returningGuests[] = (int) ($returningGuestTrend->get($key) ?? 0);

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
            'newGuests' => $newGuests,
            'payingGuests' => $payingGuests,
            'returningGuests' => $returningGuests,
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
     * Produces comparable current, previous, absolute, and percentage guest counts.
     *
     * @return array{current: int, previous: int, difference: int, percentageChange: float|null}
     */
    private function comparisonMetric(int $current, int $previous): array
    {
        $difference = $current - $previous;

        return [
            'current' => $current,
            'previous' => $previous,
            'difference' => $difference,
            'percentageChange' => $previous === 0
                ? null
                : round(($difference / $previous) * 100, 1),
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
     * Builds permission-aware destinations for the report's actionable metrics.
     *
     * @return array{
     *     guestProfiles: string|null,
     *     newGuests: string|null,
     *     payingGuests: string|null,
     *     returningGuests: null,
     *     averageSpend: string|null,
     *     grossSpend: string|null,
     *     refunds: string|null,
     *     netSpend: string|null,
     *     activity: array{hotel: string|null, conference: string|null, table: string|null, food: string|null}
     * }
     */
    public function drillDownUrls(): array
    {
        [$start, $end] = $this->periodBounds();
        $canViewGuests = auth()->check() && GuestResource::canViewAny();
        $guestProfiles = $canViewGuests ? GuestResource::getUrl('index') : null;
        $newGuests = $canViewGuests
            ? GuestResource::getUrl('index', [
                'filters' => [
                    'created_at' => [
                        'from' => $start->toDateString(),
                        'until' => $end->toDateString(),
                    ],
                ],
            ])
            : null;
        $collections = $this->paymentOrTransactionUrl('revenue');

        return [
            'guestProfiles' => $guestProfiles,
            'newGuests' => $newGuests,
            'payingGuests' => $collections,
            'returningGuests' => null,
            'averageSpend' => $collections,
            'grossSpend' => $collections,
            'refunds' => $this->paymentOrTransactionUrl('refunded', dateBasis: 'refunded_at'),
            'netSpend' => $this->transactionDashboardUrl('collection-performance'),
            'activity' => [
                'hotel' => $this->paymentOrTransactionUrl('revenue', 'hotel_bookings', 'transaction-breakdown'),
                'conference' => $this->paymentOrTransactionUrl('revenue', 'conference_bookings', 'transaction-breakdown'),
                'table' => $this->paymentOrTransactionUrl('revenue', 'table_reservations', 'transaction-breakdown'),
                'food' => $this->paymentOrTransactionUrl('revenue', 'food_orders', 'transaction-breakdown'),
            ],
        ];
    }

    /**
     * Links an individual top-guest result only when guest records are authorized.
     */
    public function guestDetailsUrl(?Guest $guest): ?string
    {
        return $guest !== null && auth()->check() && GuestResource::canViewAny()
            ? GuestResource::getUrl('view', ['record' => $guest])
            : null;
    }

    /**
     * Links finance roles to filtered payments and other authorized roles to analysis.
     */
    private function paymentOrTransactionUrl(
        string $status,
        string $transactionType = 'all',
        string $fallbackSection = 'collection-performance',
        string $dateBasis = 'created_at',
    ): ?string {
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
                'payment_method' => 'all',
                'date_basis' => $dateBasis,
                'period' => $period,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
        ]);
    }

    /**
     * Opens date-filtered transaction analysis when the current role may access it.
     */
    private function transactionDashboardUrl(string $section): ?string
    {
        if (! (auth()->check() && TransactionDashboard::canAccess())) {
            return null;
        }

        [$start, $end] = $this->periodBounds();
        $period = array_key_exists($this->period, PaymentReportFilters::periodOptions())
            ? $this->period
            : 'monthly';

        return TransactionDashboard::getUrl([
            'filters' => [
                'period' => $period,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
        ]).'#'.ltrim($section, '#');
    }

    /**
     * Builds gross guest collections in the period when funds were received.
     */
    private function collectedGuestPayments(): Builder
    {
        [$periodStart, $periodEnd] = $this->periodBounds();

        return $this->collectedGuestPaymentsBetween($periodStart, $periodEnd);
    }

    /**
     * Builds gross guest collections received within an explicit range.
     */
    private function collectedGuestPaymentsBetween(Carbon $periodStart, Carbon $periodEnd): Builder
    {
        return $this->guestPayments()
            ->whereIn('payments.payment_status', ['paid', 'completed', 'refunded', 'refund'])
            ->whereBetween('payments.created_at', [$periodStart, $periodEnd]);
    }

    /**
     * Builds guest refund events in the period when each refund was processed.
     */
    private function refundedGuestPayments(): Builder
    {
        return $this->forReportPeriod(
            $this->guestPayments()->whereNotNull('payments.refunded_at'),
            'payments.refunded_at',
        );
    }

    /**
     * Resolves the guest through the same fallback order as Payment::transactionGuest().
     */
    private function guestPayments(): Builder
    {
        return Payment::query()
            ->leftJoin('bookings as guest_report_bookings', 'payments.booking_id', '=', 'guest_report_bookings.id')
            ->leftJoin('conference_bookings as guest_report_conferences', 'payments.conference_booking_id', '=', 'guest_report_conferences.id')
            ->leftJoin('restaurant_reservations as guest_report_reservations', 'payments.restaurant_reservation_id', '=', 'guest_report_reservations.id')
            ->leftJoin('restaurant_orders as guest_report_orders', 'payments.restaurant_order_id', '=', 'guest_report_orders.id')
            ->whereRaw(self::RESOLVED_GUEST_ID.' IS NOT NULL');
    }

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }
}
