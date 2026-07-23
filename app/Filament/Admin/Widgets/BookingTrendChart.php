<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantReservation;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class BookingTrendChart extends ChartWidget
{
    protected ?string $heading = 'Booking Trends';

    protected function getData(): array
    {
        return [
            'datasets' => [
                $this->dataset('Hotel', Booking::query(), '#3b82f6'),
                $this->dataset('Conference', ConferenceBooking::query(), '#8b5cf6'),
                $this->dataset('Restaurant', RestaurantReservation::query(), '#14b8a6'),
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    private function dataset(string $label, Builder $query, string $color): array
    {
        $totals = $query
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->pluck('total', 'month');

        return [
            'label' => $label,
            'data' => collect(range(1, 12))
                ->map(fn (int $month) => (int) ($totals[$month] ?? 0))
                ->all(),
            'backgroundColor' => $color,
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }
}
