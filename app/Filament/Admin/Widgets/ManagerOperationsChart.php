<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Filament\Widgets\ChartWidget;

class ManagerOperationsChart extends ChartWidget
{
    protected ?string $heading = 'Operations — Last 7 Days';
    protected int|string|array $columnSpan = 'full';
    public static function canView(): bool { return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager']) ?? false; }
    protected function getData(): array
    {
        $labels = $hotel = $conference = $reservations = $orders = [];
        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            $date = today()->subDays($daysAgo);
            $labels[] = $date->format('D, M d');
            $hotel[] = Booking::whereDate('created_at', $date)->count();
            $conference[] = ConferenceBooking::whereDate('created_at', $date)->count();
            $reservations[] = RestaurantReservation::whereDate('created_at', $date)->count();
            $orders[] = RestaurantOrder::whereDate('created_at', $date)->count();
        }
        return ['datasets' => [
            ['label' => 'Hotel', 'data' => $hotel], ['label' => 'Conference', 'data' => $conference],
            ['label' => 'Restaurant Reservations', 'data' => $reservations], ['label' => 'Food Orders', 'data' => $orders],
        ], 'labels' => $labels];
    }
    protected function getType(): string { return 'line'; }
}
