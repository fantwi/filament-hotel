<?php

namespace App\Filament\Admin\Pages;

use App\Models\RestaurantOrder;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

/**
 * Provides the restaurant order report Filament administration page.
 */
class RestaurantOrderReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Restaurant Reports';

    protected static ?string $title = 'Restaurant Order Report';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.admin.pages.restaurant-order-report';

    public string $period = 'this_month';

    /**
     * Builds and returns orders query.
     */
    public function getOrdersQuery(): Builder
    {
        return RestaurantOrder::query()
            ->with(['guest', 'items.menuItem', 'reservation.table'])
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
        $orders = $this->getOrdersQuery()->latest()->get();
        $paidOrders = $orders->where('payment_status', 'completed');
        $outstandingOrders = $orders
            ->where('payment_status', 'pending')
            ->where('status', '!=', 'cancelled');

        $nonCancelledOrders = $orders->where('status', '!=', 'cancelled');
        $totalOrders = $orders->count();

        return [
            'orders' => $orders,
            'totalOrders' => $totalOrders,
            'totalItems' => $orders->sum(fn (RestaurantOrder $order): int => $order->items->sum('quantity')),
            'paidOrders' => $paidOrders->count(),
            'pendingOrders' => $orders->where('payment_status', 'pending')->count(),
            'cancelledOrders' => $orders->where('status', 'cancelled')->count(),
            'activeOrders' => $orders->whereIn('status', ['confirmed', 'preparing', 'ready'])->count(),
            'revenue' => $paidOrders->sum('total'),
            'outstanding' => $outstandingOrders->sum('total'),
            'averageOrderValue' => $paidOrders->avg('total') ?? 0,
            'paymentRate' => $nonCancelledOrders->isEmpty() ? 0 : round(($paidOrders->count() / $nonCancelledOrders->count()) * 100, 1),
        ];
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
