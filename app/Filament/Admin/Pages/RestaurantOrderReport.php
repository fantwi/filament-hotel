<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\InteractsWithReportPeriod;
use App\Filament\Admin\Resources\RestaurantOrders\RestaurantOrderResource;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\WithPagination;

/**
 * Provides the restaurant order report Filament administration page.
 */
class RestaurantOrderReport extends Page
{
    use InteractsWithReportPeriod;
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Restaurant Reports';

    protected static ?string $title = 'Restaurant Order Report';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.admin.pages.restaurant-order-report';

    private const PAYMENT_STATUS_OPTIONS = [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ];

    private const FULFILLMENT_STATUS_OPTIONS = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'preparing' => 'Preparing',
        'ready' => 'Ready',
        'served' => 'Served',
        'cancelled' => 'Cancelled',
    ];

    private const ORDERING_CHANNEL_OPTIONS = [
        'web' => 'Website',
        'qr' => 'Table QR',
        'staff' => 'Staff entry',
    ];

    public int $perPage = 25;

    public string $registerSearch = '';

    public string $paymentStatus = '';

    public string $fulfillmentStatus = '';

    public string $orderingChannel = '';

    /**
     * Builds and returns orders query.
     */
    public function getOrdersQuery(): Builder
    {
        return $this->forReportPeriod(
            RestaurantOrder::query(),
        );
    }

    /**
     * Returns the current page of orders without materialising the full register.
     */
    public function paginatedOrders(): LengthAwarePaginator
    {
        $perPage = max(10, min(100, $this->perPage));

        return $this->getFilteredOrdersQuery()
            ->select([
                'id',
                'guest_id',
                'order_number',
                'customer_email',
                'ordering_channel',
                'status',
                'payment_status',
                'total',
                'created_at',
            ])
            ->with('guest:id,first_name,last_name')
            ->withSum('items', 'quantity')
            ->latest()
            ->paginate($perPage, ['*'], 'orders_page');
    }

    /**
     * Applies register-only search and operational filters.
     */
    public function getFilteredOrdersQuery(): Builder
    {
        $query = $this->getOrdersQuery();
        $search = trim($this->registerSearch);

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function (Builder $searchQuery) use ($like): void {
                $searchQuery
                    ->where('order_number', 'like', $like)
                    ->orWhere('customer_email', 'like', $like)
                    ->orWhereHas('guest', function (Builder $guestQuery) use ($like): void {
                        $guestQuery
                            ->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    });
            });
        }

        if (array_key_exists($this->paymentStatus, self::PAYMENT_STATUS_OPTIONS)) {
            $query->where('payment_status', $this->paymentStatus);
        }

        if (array_key_exists($this->fulfillmentStatus, self::FULFILLMENT_STATUS_OPTIONS)) {
            $query->where('status', $this->fulfillmentStatus);
        }

        if (array_key_exists($this->orderingChannel, self::ORDERING_CHANNEL_OPTIONS)) {
            $query->where('ordering_channel', $this->orderingChannel);
        }

        return $query;
    }

    /**
     * Returns valid payment-status choices for the register.
     *
     * @return array<string, string>
     */
    public function paymentStatusOptions(): array
    {
        return self::PAYMENT_STATUS_OPTIONS;
    }

    /**
     * Returns valid fulfilment-status choices for the register.
     *
     * @return array<string, string>
     */
    public function fulfillmentStatusOptions(): array
    {
        return self::FULFILLMENT_STATUS_OPTIONS;
    }

    /**
     * Returns valid ordering-channel choices for the register.
     *
     * @return array<string, string>
     */
    public function orderingChannelOptions(): array
    {
        return self::ORDERING_CHANNEL_OPTIONS;
    }

    /**
     * Determines whether the order register is currently narrowed.
     */
    public function hasRegisterFilters(): bool
    {
        return trim($this->registerSearch) !== ''
            || array_key_exists($this->paymentStatus, self::PAYMENT_STATUS_OPTIONS)
            || array_key_exists($this->fulfillmentStatus, self::FULFILLMENT_STATUS_OPTIONS)
            || array_key_exists($this->orderingChannel, self::ORDERING_CHANNEL_OPTIONS);
    }

    /**
     * Returns a resource URL only when the current user may manage the order.
     */
    public function orderDetailsUrl(RestaurantOrder $order): ?string
    {
        return RestaurantOrderResource::canEdit($order)
            ? RestaurantOrderResource::getUrl('edit', ['record' => $order])
            : null;
    }

    /**
     * Builds and returns report data.
     */
    public function getReportData(): array
    {
        $metrics = $this->getReportMetrics();

        return [
            ...$metrics,
            ...$this->getReportAnalytics($metrics),
            'orders' => $this->paginatedOrders(),
        ];
    }

    /**
     * Calculates previous-period comparisons and zero-filled chart series.
     *
     * @param  array<string, float|int|bool>  $metrics
     * @return array{
     *     comparison: array<string, mixed>,
     *     trend: array<string, mixed>
     * }
     */
    public function getReportAnalytics(array $metrics): array
    {
        [$periodStart, $periodEnd] = $this->periodBounds();
        [$previousStart, $previousEnd] = $this->previousPeriodBounds($periodStart, $periodEnd);
        $previousOrders = $this->orderAggregates($previousStart, $previousEnd);
        $previousFinancials = $this->financialAggregates($previousStart, $previousEnd);
        $granularity = $this->trendGranularity($periodStart, $periodEnd);

        return [
            'comparison' => [
                'previousPeriodLabel' => $this->dateRangeLabel($previousStart, $previousEnd),
                'orders' => $this->comparisonMetric(
                    (float) $metrics['totalOrders'],
                    (float) $previousOrders['orders'],
                    true,
                ),
                'items' => $this->comparisonMetric(
                    (float) $metrics['totalItems'],
                    (float) $previousOrders['items'],
                    true,
                ),
                'netRevenue' => $this->comparisonMetric(
                    (float) $metrics['netRevenue'],
                    $previousFinancials['revenue'] - $previousFinancials['refunds'],
                ),
            ],
            'trend' => $this->buildTrend(
                $periodStart,
                $periodEnd,
                $granularity,
                $this->orderTrendAggregates($periodStart, $periodEnd, $granularity),
                $this->financialTrendAggregates($periodStart, $periodEnd, $granularity),
            ),
        ];
    }

    /**
     * Calculates report metrics through database aggregates.
     */
    public function getReportMetrics(): array
    {
        $liveKitchenQueue = RestaurantOrder::query()
            ->kitchenQueue()
            ->selectRaw('COUNT(*)');
        $totals = $this->getOrdersQuery()
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw("SUM(CASE WHEN payment_status = 'completed' AND status != 'cancelled' THEN 1 ELSE 0 END) as paid_orders")
            ->selectRaw("SUM(CASE WHEN payment_status = 'pending' AND status != 'cancelled' THEN 1 ELSE 0 END) as pending_orders")
            ->selectRaw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders")
            ->selectRaw("SUM(CASE WHEN status IN ('confirmed', 'preparing', 'ready') THEN 1 ELSE 0 END) as active_orders")
            ->selectRaw("SUM(CASE WHEN status != 'cancelled' THEN 1 ELSE 0 END) as non_cancelled_orders")
            ->selectSub($liveKitchenQueue, 'live_kitchen_orders')
            ->first();

        $totalItems = RestaurantOrderItem::query()
            ->whereHas('order', fn (Builder $query): Builder => $this->forReportPeriod($query))
            ->sum('quantity');

        $paidOrders = (int) ($totals->paid_orders ?? 0);
        $totalOrders = (int) ($totals->total_orders ?? 0);
        $nonCancelledOrders = (int) ($totals->non_cancelled_orders ?? 0);
        $financials = $this->getFinancialMetrics();

        return [
            'totalOrders' => $totalOrders,
            'totalItems' => (int) $totalItems,
            'paidOrders' => $paidOrders,
            'pendingOrders' => (int) ($totals->pending_orders ?? 0),
            'cancelledOrders' => (int) ($totals->cancelled_orders ?? 0),
            'activeOrders' => (int) ($totals->active_orders ?? 0),
            'liveKitchenOrders' => (int) ($totals->live_kitchen_orders ?? 0),
            'revenue' => $financials['revenue'],
            'refunds' => $financials['refunds'],
            'netRevenue' => $financials['netRevenue'],
            'outstanding' => $financials['outstanding'],
            'collectedOrderCount' => $financials['collectedOrderCount'],
            'averageOrderValue' => $financials['averageOrderValue'],
            'paymentRate' => $nonCancelledOrders === 0 ? 0 : round(($paidOrders / $nonCancelledOrders) * 100, 1),
            'hasPeriodActivity' => $totalOrders > 0
                || $financials['collectedOrderCount'] > 0
                || $financials['refunds'] > 0,
        ];
    }

    /**
     * Calculates collection, refund, and remaining-balance metrics from payment events.
     *
     * @return array{revenue: float, refunds: float, netRevenue: float, outstanding: float, collectedOrderCount: int, averageOrderValue: float}
     */
    private function getFinancialMetrics(): array
    {
        [$periodStart, $periodEnd] = $this->periodBounds();
        $collections = Payment::query()
            ->whereNotNull('restaurant_order_id')
            ->whereIn('payment_status', ['paid', 'completed', 'refunded', 'refund'])
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->selectRaw('COALESCE(SUM(amount), 0) as revenue')
            ->selectRaw('COUNT(DISTINCT restaurant_order_id) as collected_order_count')
            ->first();
        $revenue = (float) ($collections->revenue ?? 0);
        $collectedOrderCount = (int) ($collections->collected_order_count ?? 0);
        $refunds = (float) Payment::query()
            ->whereNotNull('restaurant_order_id')
            ->whereNotNull('refunded_at')
            ->whereBetween('refunded_at', [$periodStart, $periodEnd])
            ->sum('amount');

        $paidPaymentsAlias = 'restaurant_order_report_paid';
        $paidPayments = Payment::query()
            ->select('restaurant_order_id')
            ->selectRaw('SUM(amount) as paid_amount')
            ->whereIn('payment_status', ['paid', 'completed'])
            ->whereNotNull('restaurant_order_id')
            ->groupBy('restaurant_order_id');
        $remainingBalance = "restaurant_orders.total - COALESCE({$paidPaymentsAlias}.paid_amount, 0)";
        $outstanding = $this->forReportPeriod(
            RestaurantOrder::query(),
            'restaurant_orders.created_at',
        )
            ->leftJoinSub(
                $paidPayments,
                $paidPaymentsAlias,
                "{$paidPaymentsAlias}.restaurant_order_id",
                '=',
                'restaurant_orders.id',
            )
            ->where('restaurant_orders.status', '!=', 'cancelled')
            ->whereNotIn('restaurant_orders.payment_status', ['paid', 'completed', 'refunded'])
            ->whereRaw("{$remainingBalance} > 0")
            ->selectRaw("COALESCE(SUM({$remainingBalance}), 0) as outstanding")
            ->value('outstanding');

        return [
            'revenue' => $revenue,
            'refunds' => $refunds,
            'netRevenue' => $revenue - $refunds,
            'outstanding' => (float) $outstanding,
            'collectedOrderCount' => $collectedOrderCount,
            'averageOrderValue' => $collectedOrderCount === 0 ? 0.0 : $revenue / $collectedOrderCount,
        ];
    }

    /**
     * Returns the immediately preceding inclusive period with the same day count.
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
     * Calculates order and item totals in one query for a comparison period.
     *
     * @return array{orders: int, items: int}
     */
    private function orderAggregates(Carbon $periodStart, Carbon $periodEnd): array
    {
        $totals = RestaurantOrder::query()
            ->leftJoin(
                'restaurant_order_items',
                'restaurant_order_items.restaurant_order_id',
                '=',
                'restaurant_orders.id',
            )
            ->whereBetween('restaurant_orders.created_at', [$periodStart, $periodEnd])
            ->selectRaw('COUNT(DISTINCT restaurant_orders.id) as total_orders')
            ->selectRaw('COALESCE(SUM(restaurant_order_items.quantity), 0) as total_items')
            ->first();

        return [
            'orders' => (int) ($totals->total_orders ?? 0),
            'items' => (int) ($totals->total_items ?? 0),
        ];
    }

    /**
     * Calculates collection and refund movements in one comparison-period query.
     *
     * @return array{revenue: float, refunds: float}
     */
    private function financialAggregates(Carbon $periodStart, Carbon $periodEnd): array
    {
        $totals = Payment::query()
            ->whereNotNull('restaurant_order_id')
            ->where(function (Builder $query) use ($periodStart, $periodEnd): void {
                $query
                    ->where(function (Builder $collectionQuery) use ($periodStart, $periodEnd): void {
                        $collectionQuery
                            ->whereIn('payment_status', ['paid', 'completed', 'refunded', 'refund'])
                            ->whereBetween('created_at', [$periodStart, $periodEnd]);
                    })
                    ->orWhereBetween('refunded_at', [$periodStart, $periodEnd]);
            })
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN payment_status IN ('paid', 'completed', 'refunded', 'refund') AND created_at BETWEEN ? AND ? THEN amount ELSE 0 END), 0) as revenue",
                [$periodStart, $periodEnd],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN refunded_at BETWEEN ? AND ? THEN amount ELSE 0 END), 0) as refunds',
                [$periodStart, $periodEnd],
            )
            ->first();

        return [
            'revenue' => (float) ($totals->revenue ?? 0),
            'refunds' => (float) ($totals->refunds ?? 0),
        ];
    }

    /**
     * Selects a readable chart resolution without generating excessive buckets.
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
     * Groups order counts and item quantities into one set of time buckets.
     *
     * @return Collection<string, object>
     */
    private function orderTrendAggregates(
        Carbon $periodStart,
        Carbon $periodEnd,
        string $granularity,
    ): Collection {
        $expression = $this->bucketExpression('restaurant_orders.created_at', $granularity);

        return RestaurantOrder::query()
            ->leftJoin(
                'restaurant_order_items',
                'restaurant_order_items.restaurant_order_id',
                '=',
                'restaurant_orders.id',
            )
            ->whereBetween('restaurant_orders.created_at', [$periodStart, $periodEnd])
            ->selectRaw("{$expression} as bucket")
            ->selectRaw('COUNT(DISTINCT restaurant_orders.id) as order_count')
            ->selectRaw('COALESCE(SUM(restaurant_order_items.quantity), 0) as item_count')
            ->groupByRaw($expression)
            ->get()
            ->keyBy(fn (object $aggregate): string => (string) $aggregate->bucket);
    }

    /**
     * Groups collection and refund events in one database round trip.
     *
     * @return Collection<string, array{collected: float, refunds: float}>
     */
    private function financialTrendAggregates(
        Carbon $periodStart,
        Carbon $periodEnd,
        string $granularity,
    ): Collection {
        $collectionExpression = $this->bucketExpression('created_at', $granularity);
        $refundExpression = $this->bucketExpression('refunded_at', $granularity);
        $collections = Payment::query()
            ->whereNotNull('restaurant_order_id')
            ->whereIn('payment_status', ['paid', 'completed', 'refunded', 'refund'])
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->selectRaw("{$collectionExpression} as bucket")
            ->selectRaw('SUM(amount) as collected')
            ->selectRaw('0 as refunds')
            ->groupByRaw($collectionExpression);
        $refunds = Payment::query()
            ->whereNotNull('restaurant_order_id')
            ->whereNotNull('refunded_at')
            ->whereBetween('refunded_at', [$periodStart, $periodEnd])
            ->selectRaw("{$refundExpression} as bucket")
            ->selectRaw('0 as collected')
            ->selectRaw('SUM(amount) as refunds')
            ->groupByRaw($refundExpression);

        return $collections
            ->unionAll($refunds)
            ->get()
            ->groupBy(fn (object $aggregate): string => (string) $aggregate->bucket)
            ->map(fn (Collection $aggregates): array => [
                'collected' => (float) $aggregates->sum('collected'),
                'refunds' => (float) $aggregates->sum('refunds'),
            ]);
    }

    /**
     * Builds every selected-period chart bucket, including periods without activity.
     *
     * @param  Collection<string, object>  $orderTrend
     * @param  Collection<string, array{collected: float, refunds: float}>  $financialTrend
     * @return array{
     *     granularity: string,
     *     labels: list<string>,
     *     orders: list<int>,
     *     items: list<int>,
     *     collected: list<float>,
     *     refunds: list<float>,
     *     netRevenue: list<float>
     * }
     */
    private function buildTrend(
        Carbon $periodStart,
        Carbon $periodEnd,
        string $granularity,
        Collection $orderTrend,
        Collection $financialTrend,
    ): array {
        $cursor = match ($granularity) {
            'hour' => $periodStart->copy()->startOfHour(),
            'day' => $periodStart->copy()->startOfDay(),
            'month' => $periodStart->copy()->startOfMonth(),
            default => $periodStart->copy()->startOfYear(),
        };
        $labels = [];
        $orders = [];
        $items = [];
        $collected = [];
        $refunds = [];

        while ($cursor->lessThanOrEqualTo($periodEnd)) {
            $key = $this->bucketKey($cursor, $granularity);
            $orderAggregate = $orderTrend->get($key);
            $financialAggregate = $financialTrend->get($key, ['collected' => 0.0, 'refunds' => 0.0]);

            $labels[] = match ($granularity) {
                'hour' => $cursor->format('g A'),
                'day' => $cursor->format('M j'),
                'month' => $cursor->format('M Y'),
                default => $cursor->format('Y'),
            };
            $orders[] = (int) ($orderAggregate?->order_count ?? 0);
            $items[] = (int) ($orderAggregate?->item_count ?? 0);
            $collected[] = (float) $financialAggregate['collected'];
            $refunds[] = (float) $financialAggregate['refunds'];

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
            'orders' => $orders,
            'items' => $items,
            'collected' => $collected,
            'refunds' => $refunds,
            'netRevenue' => array_map(
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
     * Formats a chart cursor to match its database aggregate key.
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
     * Produces current, previous, absolute-change, and percentage values.
     *
     * @return array{current: float|int, previous: float|int, difference: float|int, percentageChange: float|null}
     */
    private function comparisonMetric(float $current, float $previous, bool $wholeNumbers = false): array
    {
        $difference = $current - $previous;

        return [
            'current' => $wholeNumbers ? (int) $current : $current,
            'previous' => $wholeNumbers ? (int) $previous : $previous,
            'difference' => $wholeNumbers ? (int) $difference : $difference,
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
     * Resets pagination when the page size changes.
     */
    public function updatedPerPage(): void
    {
        $this->resetPage('orders_page');
    }

    /**
     * Resets the register to its first page after the search changes.
     */
    public function updatedRegisterSearch(): void
    {
        $this->resetPage('orders_page');
    }

    /**
     * Resets the register to its first page after the payment filter changes.
     */
    public function updatedPaymentStatus(): void
    {
        $this->resetPage('orders_page');
    }

    /**
     * Resets the register to its first page after the fulfilment filter changes.
     */
    public function updatedFulfillmentStatus(): void
    {
        $this->resetPage('orders_page');
    }

    /**
     * Resets the register to its first page after the channel filter changes.
     */
    public function updatedOrderingChannel(): void
    {
        $this->resetPage('orders_page');
    }

    /**
     * Clears every order-register filter and restores its first page.
     */
    public function resetRegisterFilters(): void
    {
        $this->registerSearch = '';
        $this->paymentStatus = '';
        $this->fulfillmentStatus = '';
        $this->orderingChannel = '';
        $this->resetPage('orders_page');
    }

    /**
     * Identifies the report paginator reset after a period is applied.
     */
    protected function reportPaginatorName(): ?string
    {
        return 'orders_page';
    }

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->can('view restaurant reports')
            || $user?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant'])
            || false;
    }
}
