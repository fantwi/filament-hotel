<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Filament\Widgets\ChartWidget;

/**
 * Provides the manager operations chart Filament dashboard widget.
 */
class ManagerOperationsChart extends ChartWidget
{
    use InteractsWithDashboardDateRange;

    protected ?string $heading = 'Operations by Selected Date Range';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager']) ?? false;
    }

    /**
     * Builds and returns data.
     */
    protected function getData(): array
    {
        [$start, $end] = $this->dashboardDateRange();
        $dailyTotals = fn (string $model): array => $this->forDashboardDateRange($model::query())
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->all();

        $hotelTotals = $dailyTotals(Booking::class);
        $conferenceTotals = $dailyTotals(ConferenceBooking::class);
        $reservationTotals = $dailyTotals(RestaurantReservation::class);
        $orderTotals = $dailyTotals(RestaurantOrder::class);
        $labels = $hotel = $conference = $reservations = $orders = [];

        for ($date = $start->copy()->startOfDay(); $date->lessThanOrEqualTo($end); $date->addDay()) {
            $key = $date->toDateString();
            $labels[] = $date->format('M d');
            $hotel[] = (int) ($hotelTotals[$key] ?? 0);
            $conference[] = (int) ($conferenceTotals[$key] ?? 0);
            $reservations[] = (int) ($reservationTotals[$key] ?? 0);
            $orders[] = (int) ($orderTotals[$key] ?? 0);
        }

        return ['datasets' => [
            ['label' => 'Hotel', 'data' => $hotel], ['label' => 'Conference', 'data' => $conference],
            ['label' => 'Restaurant Reservations', 'data' => $reservations], ['label' => 'Food Orders', 'data' => $orders],
        ], 'labels' => $labels];
    }

    /**
     * Builds and returns type.
     */
    protected function getType(): string
    {
        return 'line';
    }
}
