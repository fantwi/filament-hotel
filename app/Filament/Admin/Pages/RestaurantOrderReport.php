<?php

namespace App\Filament\Admin\Pages;

use App\Models\RestaurantOrder;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class RestaurantOrderReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Restaurant Reports';

    protected static ?string $title = 'Restaurant Order Report';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.admin.pages.restaurant-order-report';

    public string $period = 'this_month';

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

    public function getReportData(): array
    {
        $orders = $this->getOrdersQuery()->latest()->get();
        $paidOrders = $orders->where('payment_status', 'completed');
        $outstandingOrders = $orders
            ->where('payment_status', 'pending')
            ->where('status', '!=', 'cancelled');

        return [
            'orders' => $orders,
            'totalOrders' => $orders->count(),
            'paidOrders' => $paidOrders->count(),
            'pendingOrders' => $orders->where('payment_status', 'pending')->count(),
            'cancelledOrders' => $orders->where('status', 'cancelled')->count(),
            'revenue' => $paidOrders->sum('total'),
            'outstanding' => $outstandingOrders->sum('total'),
            'averageOrderValue' => $paidOrders->avg('total') ?? 0,
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->can('view restaurant reports')
            || $user?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant'])
            || false;
    }
}
