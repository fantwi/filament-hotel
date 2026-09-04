<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\InteractsWithReportPeriod;
use App\Models\Guest;
use App\Models\Payment;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Provides the guest report Filament administration page.
 */
class GuestReport extends Page
{
    use InteractsWithReportPeriod;

    private const RESOLVED_GUEST_ID = 'COALESCE(payments.guest_id, guest_report_bookings.guest_id, guest_report_conferences.guest_id, guest_report_reservations.guest_id, guest_report_orders.guest_id)';

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
        $paidPayments = $this->paidGuestPayments();

        $payingGuests = (clone $paidPayments)
            ->distinct()
            ->count(DB::raw(self::RESOLVED_GUEST_ID));

        $totalPaid = (float) (clone $paidPayments)->sum('payments.amount');

        $topGuests = (clone $paidPayments)
            ->with('guest')
            ->selectRaw(self::RESOLVED_GUEST_ID.' as guest_id, SUM(payments.amount) as total_spend, COUNT(*) as payment_count')
            ->groupByRaw(self::RESOLVED_GUEST_ID)
            ->orderByDesc('total_spend')
            ->limit(5)
            ->get();

        $activity = [
            'hotel' => (clone $paidPayments)->whereNotNull('payments.booking_id')->count(),
            'conference' => (clone $paidPayments)->whereNotNull('payments.conference_booking_id')->count(),
            'table' => (clone $paidPayments)->whereNotNull('payments.restaurant_reservation_id')->count(),
            'food' => (clone $paidPayments)->whereNotNull('payments.restaurant_order_id')->count(),
        ];

        return [
            'totalGuests' => Guest::count(),
            'newGuests' => $this->forReportPeriod(Guest::query())->count(),
            'payingGuests' => $payingGuests,
            'returningGuests' => (clone $paidPayments)
                ->selectRaw(self::RESOLVED_GUEST_ID.' as guest_id')
                ->groupByRaw(self::RESOLVED_GUEST_ID)
                ->havingRaw('COUNT(*) >= 2')
                ->get()
                ->count(),
            'averageSpend' => $payingGuests > 0 ? $totalPaid / $payingGuests : 0,
            'totalPaid' => $totalPaid,
            'paymentCount' => (clone $paidPayments)->count('payments.id'),
            'activity' => $activity,
            'topGuests' => $topGuests,
        ];
    }

    /**
     * Builds the paid-payment scope with the same guest fallback order used by
     * Payment::transactionGuest(), including legacy rows without a direct guest.
     */
    private function paidGuestPayments(): Builder
    {
        return $this->forReportPeriod(
            Payment::query()
                ->leftJoin('bookings as guest_report_bookings', 'payments.booking_id', '=', 'guest_report_bookings.id')
                ->leftJoin('conference_bookings as guest_report_conferences', 'payments.conference_booking_id', '=', 'guest_report_conferences.id')
                ->leftJoin('restaurant_reservations as guest_report_reservations', 'payments.restaurant_reservation_id', '=', 'guest_report_reservations.id')
                ->leftJoin('restaurant_orders as guest_report_orders', 'payments.restaurant_order_id', '=', 'guest_report_orders.id')
                ->whereIn('payments.payment_status', ['paid', 'completed'])
                ->whereRaw(self::RESOLVED_GUEST_ID.' IS NOT NULL'),
            'payments.created_at',
        );
    }

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }
}
