<?php

namespace App\Filament\Admin\Concerns;

use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Filament\Admin\Resources\RestaurantOrders\RestaurantOrderResource;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Builds date-aware links from dashboard statistics to their source records.
 */
trait BuildsDashboardDrillDowns
{
    /**
     * Links a statistic to the payments register with the current period applied.
     */
    protected function paymentDrillDownUrl(string $status): string
    {
        [$start, $end] = $this->dashboardDateRange();

        return PaymentResource::getUrl('index', [
            'filters' => [
                'transaction_type' => 'all',
                'payment_status' => $status,
                'period' => (string) ($this->pageFilters['period'] ?? 'monthly'),
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
        ]);
    }

    /**
     * Links a statistic to food orders with matching date and optional status filters.
     */
    protected function restaurantOrderDrillDownUrl(?string $status = null): string
    {
        [$start, $end] = $this->dashboardDateRange();
        $filters = [
            'created_at' => [
                'created_from' => $start->toDateString(),
                'created_until' => $end->toDateString(),
            ],
        ];

        if ($status !== null) {
            $filters['status'] = ['value' => $status];
        }

        return RestaurantOrderResource::getUrl('index', [
            'filters' => $filters,
        ]);
    }

    /**
     * Makes an actionable statistic visibly and accessibly clickable.
     */
    protected function drillDown(Stat $stat, string $url): Stat
    {
        return $stat
            ->url($url)
            ->descriptionIcon('heroicon-m-arrow-top-right-on-square');
    }
}
