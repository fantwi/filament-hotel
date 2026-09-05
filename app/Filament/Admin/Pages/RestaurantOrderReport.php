<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\InteractsWithReportPeriod;
use App\Filament\Admin\Resources\RestaurantOrders\RestaurantOrderResource;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
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
            'orders' => $this->paginatedOrders(),
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
