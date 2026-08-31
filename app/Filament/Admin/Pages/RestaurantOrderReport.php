<?php

namespace App\Filament\Admin\Pages;

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
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Restaurant Reports';

    protected static ?string $title = 'Restaurant Order Report';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.admin.pages.restaurant-order-report';

    public string $period = 'this_month';

    public int $perPage = 25;

    /**
     * Builds and returns orders query.
     */
    public function getOrdersQuery(): Builder
    {
        return $this->applyPeriod(
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
     * Configures period label for the Filament administration interface.
     */
    public function periodLabel(): string
    {
        return match ($this->period) {
            'today' => 'Today',
            'this_week' => 'This week',
            'this_year' => 'This year',
            'all' => 'All time',
            default => 'This month',
        };
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
            ->selectRaw("SUM(CASE WHEN payment_status = 'completed' THEN 1 ELSE 0 END) as paid_orders")
            ->selectRaw("SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_orders")
            ->selectRaw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders")
            ->selectRaw("SUM(CASE WHEN status IN ('confirmed', 'preparing', 'ready') THEN 1 ELSE 0 END) as active_orders")
            ->selectRaw("SUM(CASE WHEN payment_status = 'completed' THEN total ELSE 0 END) as revenue")
            ->selectRaw("SUM(CASE WHEN payment_status = 'pending' AND status != 'cancelled' THEN total ELSE 0 END) as outstanding")
            ->selectRaw('AVG(CASE WHEN payment_status = \'completed\' THEN total END) as average_order_value')
            ->selectRaw("SUM(CASE WHEN status != 'cancelled' THEN 1 ELSE 0 END) as non_cancelled_orders")
            ->first();

        $totalItems = RestaurantOrderItem::query()
            ->whereHas('order', fn (Builder $query): Builder => $this->applyPeriod($query))
            ->sum('quantity');

        $paidOrders = (int) ($totals->paid_orders ?? 0);
        $nonCancelledOrders = (int) ($totals->non_cancelled_orders ?? 0);

        return [
            'totalOrders' => (int) ($totals->total_orders ?? 0),
            'totalItems' => (int) $totalItems,
            'paidOrders' => $paidOrders,
            'pendingOrders' => (int) ($totals->pending_orders ?? 0),
            'cancelledOrders' => (int) ($totals->cancelled_orders ?? 0),
            'activeOrders' => (int) ($totals->active_orders ?? 0),
            'revenue' => (float) ($totals->revenue ?? 0),
            'outstanding' => (float) ($totals->outstanding ?? 0),
            'averageOrderValue' => (float) ($totals->average_order_value ?? 0),
            'paymentRate' => $nonCancelledOrders === 0 ? 0 : round(($paidOrders / $nonCancelledOrders) * 100, 1),
        ];
    }

    /**
     * Resets pagination when the selected reporting period changes.
     */
    public function updatedPeriod(): void
    {
        $this->resetPage('orders_page');
    }

    /**
     * Resets pagination when the page size changes.
     */
    public function updatedPerPage(): void
    {
        $this->resetPage('orders_page');
    }

    /**
     * Applies the selected reporting period to a query.
     */
    private function applyPeriod(Builder $query): Builder
    {
        return $query
            ->when(
                $this->period === 'today',
                fn (Builder $query) => $query->whereDate('created_at', today()),
            )
            ->when(
                $this->period === 'this_week',
                fn (Builder $query) => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            )
            ->when(
                $this->period === 'this_month',
                fn (Builder $query) => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]),
            )
            ->when(
                $this->period === 'this_year',
                fn (Builder $query) => $query->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()]),
            );
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
