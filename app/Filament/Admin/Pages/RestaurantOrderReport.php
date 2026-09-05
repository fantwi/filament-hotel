<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\InteractsWithReportPeriod;
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

    public int $perPage = 25;

    /**
     * Builds and returns orders query.
     */
    public function getOrdersQuery(): Builder
    {
        return $this->forReportPeriod(
            RestaurantOrder::query()->with(['guest', 'items']),
        );
    }

    /**
     * Returns the current page of orders without materialising the full register.
     */
    public function paginatedOrders(): LengthAwarePaginator
    {
        $perPage = max(10, min(100, $this->perPage));

        return $this->getOrdersQuery()
            ->latest()
            ->paginate($perPage, ['*'], 'orders_page');
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
        $totals = $this->getOrdersQuery()
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw("SUM(CASE WHEN payment_status = 'completed' AND status != 'cancelled' THEN 1 ELSE 0 END) as paid_orders")
            ->selectRaw("SUM(CASE WHEN payment_status = 'pending' AND status != 'cancelled' THEN 1 ELSE 0 END) as pending_orders")
            ->selectRaw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders")
            ->selectRaw("SUM(CASE WHEN status IN ('confirmed', 'preparing', 'ready') THEN 1 ELSE 0 END) as active_orders")
            ->selectRaw("SUM(CASE WHEN status != 'cancelled' THEN 1 ELSE 0 END) as non_cancelled_orders")
            ->first();

        $totalItems = RestaurantOrderItem::query()
            ->whereHas('order', fn (Builder $query): Builder => $this->forReportPeriod($query))
            ->sum('quantity');

        $paidOrders = (int) ($totals->paid_orders ?? 0);
        $nonCancelledOrders = (int) ($totals->non_cancelled_orders ?? 0);
        $financials = $this->getFinancialMetrics();

        return [
            'totalOrders' => (int) ($totals->total_orders ?? 0),
            'totalItems' => (int) $totalItems,
            'paidOrders' => $paidOrders,
            'pendingOrders' => (int) ($totals->pending_orders ?? 0),
            'cancelledOrders' => (int) ($totals->cancelled_orders ?? 0),
            'activeOrders' => (int) ($totals->active_orders ?? 0),
            'revenue' => $financials['revenue'],
            'refunds' => $financials['refunds'],
            'netRevenue' => $financials['netRevenue'],
            'outstanding' => $financials['outstanding'],
            'collectedOrderCount' => $financials['collectedOrderCount'],
            'averageOrderValue' => $financials['averageOrderValue'],
            'paymentRate' => $nonCancelledOrders === 0 ? 0 : round(($paidOrders / $nonCancelledOrders) * 100, 1),
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
