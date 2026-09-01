<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\InteractsWithReportPeriod;
use App\Models\Guest;
use App\Models\Payment;
use Filament\Pages\Page;

/**
 * Provides the guest report Filament administration page.
 */
class GuestReport extends Page
{
    use InteractsWithReportPeriod;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Guest Report';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.admin.pages.guest-report';

    /**
     * Configures report for the Filament administration interface.
     */
    public function report(): array
    {
        $paidPayments = $this->forReportPeriod(
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
            'newGuests' => $this->forReportPeriod(Guest::query())->count(),
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

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }
}
