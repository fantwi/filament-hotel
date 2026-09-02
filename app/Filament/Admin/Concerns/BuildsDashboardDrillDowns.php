<?php

namespace App\Filament\Admin\Concerns;

use App\Filament\Admin\Pages\BookingCalendar;
use App\Filament\Admin\Pages\CorporateReceivables;
use App\Filament\Admin\Pages\Dashboards\TransactionDashboard;
use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use App\Filament\Admin\Resources\KitchenStockMovements\KitchenStockMovementResource;
use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Filament\Admin\Resources\RestaurantOrders\RestaurantOrderResource;
use App\Filament\Admin\Resources\RestaurantReservations\RestaurantReservationResource;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Builds date-aware links from dashboard statistics to their source records.
 */
trait BuildsDashboardDrillDowns
{
    /**
     * Links hotel arrivals to bookings whose check-in falls in the period.
     */
    protected function bookingArrivalsDrillDownUrl(): string
    {
        [$start, $end] = $this->dashboardDateRange();

        return BookingResource::getUrl('index', [
            'filters' => [
                'check_in' => [
                    'from' => $start->toDateString(),
                    'until' => $end->toDateString(),
                ],
            ],
        ]);
    }

    /**
     * Links a statistic to active hotel stays overlapping the current period.
     */
    protected function bookingDrillDownUrl(?string $status = null): string
    {
        [$start, $end] = $this->dashboardDateRange();
        $filters = [
            'active_period' => [
                'from' => $start->toDateString(),
                'until' => $end->toDateString(),
            ],
        ];

        if ($status !== null) {
            $filters['status'] = ['value' => $status];
        }

        return BookingResource::getUrl('index', [
            'filters' => $filters,
        ]);
    }

    /**
     * Links hotel departures to bookings whose check-out falls in the period.
     */
    protected function bookingDeparturesDrillDownUrl(): string
    {
        [$start, $end] = $this->dashboardDateRange();

        return BookingResource::getUrl('index', [
            'filters' => [
                'check_out' => [
                    'from' => $start->toDateString(),
                    'until' => $end->toDateString(),
                ],
            ],
        ]);
    }

    /**
     * Links arrival balances to bookings that still require payment.
     */
    protected function bookingOutstandingArrivalsDrillDownUrl(): string
    {
        [$start, $end] = $this->dashboardDateRange();

        return BookingResource::getUrl('index', [
            'filters' => [
                'check_in' => [
                    'from' => $start->toDateString(),
                    'until' => $end->toDateString(),
                ],
                'balance' => [
                    'value' => 'outstanding',
                ],
            ],
        ]);
    }

    /**
     * Links a statistic to the conference schedule focused on this period.
     */
    protected function conferenceBookingDrillDownUrl(): string
    {
        [$start, $end] = $this->dashboardDateRange();

        return BookingCalendar::getUrl([
            'type' => 'conference',
            'status_scope' => 'active',
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ]);
    }

    /**
     * Links all conference events in the selected period to the calendar.
     */
    protected function conferenceEventsDrillDownUrl(): string
    {
        [$start, $end] = $this->dashboardDateRange();

        return BookingCalendar::getUrl([
            'type' => 'conference',
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ]);
    }

    /**
     * Links a statistic to active table reservations in the current period.
     */
    protected function restaurantReservationDrillDownUrl(): string
    {
        [$start, $end] = $this->dashboardDateRange();

        return RestaurantReservationResource::getUrl('index', [
            'filters' => [
                'active_period' => [
                    'from' => $start->toDateString(),
                    'until' => $end->toDateString(),
                ],
            ],
        ]);
    }

    /**
     * Links restaurant activity to reservations dated within the period.
     */
    protected function restaurantReservationActivityDrillDownUrl(): string
    {
        [$start, $end] = $this->dashboardDateRange();

        return RestaurantReservationResource::getUrl('index', [
            'filters' => [
                'reservation_date' => [
                    'from' => $start->toDateString(),
                    'until' => $end->toDateString(),
                ],
            ],
        ]);
    }

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
     * Links production activity to batches recorded within the period.
     */
    protected function kitchenProductionDrillDownUrl(): string
    {
        [$start, $end] = $this->dashboardDateRange();

        return KitchenProductionResource::getUrl('index', [
            'filters' => [
                'production_date' => [
                    'from' => $start->toDateString(),
                    'until' => $end->toDateString(),
                ],
            ],
        ]);
    }

    /**
     * Links stock activity to movements recorded within the period.
     */
    protected function kitchenStockMovementDrillDownUrl(): string
    {
        [$start, $end] = $this->dashboardDateRange();

        return KitchenStockMovementResource::getUrl('index', [
            'filters' => [
                'occurred_at' => [
                    'from' => $start->toDateString(),
                    'until' => $end->toDateString(),
                ],
            ],
        ]);
    }

    /**
     * Links combined transaction metrics to the detailed channel breakdown.
     */
    protected function transactionDashboardDrillDownUrl(): string
    {
        [$start, $end] = $this->dashboardDateRange();

        return TransactionDashboard::getUrl([
            'filters' => [
                'period' => (string) ($this->pageFilters['period'] ?? 'monthly'),
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
        ]);
    }

    /**
     * Links a current-balance statistic to the corporate settlement queue.
     */
    protected function corporateReceivablesDrillDownUrl(): string
    {
        return CorporateReceivables::getUrl();
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
