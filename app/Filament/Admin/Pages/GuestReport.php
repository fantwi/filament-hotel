<?php

namespace App\Filament\Admin\Pages;

use App\Models\Guest;
use App\Models\Payment;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class GuestReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Guest Report';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.admin.pages.guest-report';

    public string $period = 'this_month';

    public function periodLabel(): string
    {
        return match ($this->period) {
            'today' => 'Today',
            'this_week' => 'This week',
            'this_quarter' => 'This quarter',
            'this_year' => 'This year',
            'all' => 'All time',
            default => 'This month',
        };
    }

    public function report(): array
    {
        $paidPayments = $this->forPeriod(
            Payment::query()
                ->whereIn('payment_status', ['paid', 'completed'])
                ->whereNotNull('guest_id'),
        );

        $payingGuests = (clone $paidPayments)
            ->distinct()
            ->count('guest_id');

        $totalPaid = (float) (clone $paidPayments)->sum('amount');

        $topGuests = (clone $paidPayments)
            ->with('guest')
            ->selectRaw('guest_id, SUM(amount) as total_spend, COUNT(*) as payment_count')
            ->groupBy('guest_id')
            ->orderByDesc('total_spend')
            ->limit(5)
            ->get();

        $activity = [
            'hotel' => (clone $paidPayments)->whereNotNull('booking_id')->count(),
            'conference' => (clone $paidPayments)->whereNotNull('conference_booking_id')->count(),
            'table' => (clone $paidPayments)->whereNotNull('restaurant_reservation_id')->count(),
            'food' => (clone $paidPayments)->whereNotNull('restaurant_order_id')->count(),
        ];

        return [
            'totalGuests' => Guest::count(),
            'newGuests' => $this->forPeriod(Guest::query())->count(),
            'payingGuests' => $payingGuests,
            'returningGuests' => (clone $paidPayments)
                ->select('guest_id')
                ->groupBy('guest_id')
                ->havingRaw('COUNT(*) >= 2')
                ->get()
                ->count(),
            'averageSpend' => $payingGuests > 0 ? $totalPaid / $payingGuests : 0,
            'totalPaid' => $totalPaid,
            'paymentCount' => (clone $paidPayments)->count(),
            'activity' => $activity,
            'topGuests' => $topGuests,
        ];
    }

    private function forPeriod(Builder $query, string $column = 'created_at'): Builder
    {
        return match ($this->period) {
            'today' => $query->whereDate($column, today()),
            'this_week' => $query->whereBetween($column, [now()->startOfWeek(), now()->endOfWeek()]),
            'this_quarter' => $query->whereBetween($column, [now()->startOfQuarter(), now()->endOfQuarter()]),
            'this_year' => $query->whereBetween($column, [now()->startOfYear(), now()->endOfYear()]),
            'all' => $query,
            default => $query->whereBetween($column, [now()->startOfMonth(), now()->endOfMonth()]),
        };
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }
}
