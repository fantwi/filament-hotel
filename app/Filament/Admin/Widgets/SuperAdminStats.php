<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides the super admin stats Filament dashboard widget.
 */
class SuperAdminStats extends StatsOverviewWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view super admin dashboard') ?? false;
    }

    /**
     * Builds and returns stats.
     */
    protected function getStats(): array
    {
        $revenue = $this->forDashboardDateRange(Payment::query())->whereIn('payment_status', ['paid', 'completed'])->sum('amount');
        $reservations = $this->forDashboardDateRange(Booking::query())->count()
            + $this->forDashboardDateRange(ConferenceBooking::query())->count()
            + $this->forDashboardDateRange(RestaurantReservation::query())->count();

        return [
            Stat::make('New System Users', number_format($this->forDashboardDateRange(User::query())->count()))->description($this->dashboardDateRangeLabel())->icon('heroicon-o-users')->color('primary'),
            Stat::make('Registered Guests', number_format($this->forDashboardDateRange(Guest::query())->count()))->description('Added in selected range')->icon('heroicon-o-user-group')->color('info'),
            Stat::make('All Reservations', number_format($reservations))->description('Created in selected range')->icon('heroicon-o-calendar-days')->color('warning'),
            Stat::make('Restaurant Orders', number_format($this->forDashboardDateRange(RestaurantOrder::query())->count()))->description('Created in selected range')->icon('heroicon-o-shopping-bag')->color('gray'),
            Stat::make('Total Revenue', 'GHS '.number_format($revenue, 2))->description($this->dashboardDateRangeLabel())->icon('heroicon-o-banknotes')->color('success'),
        ];
    }
}
