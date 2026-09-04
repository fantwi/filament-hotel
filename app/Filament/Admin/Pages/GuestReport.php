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

    private const DISTINCT_SERVICE_VISIT_COUNT = 'COUNT(DISTINCT payments.booking_id) + COUNT(DISTINCT CASE WHEN payments.booking_id IS NULL THEN payments.conference_booking_id END) + COUNT(DISTINCT CASE WHEN payments.booking_id IS NULL AND payments.conference_booking_id IS NULL THEN payments.restaurant_reservation_id END) + COUNT(DISTINCT CASE WHEN payments.booking_id IS NULL AND payments.conference_booking_id IS NULL AND payments.restaurant_reservation_id IS NULL THEN payments.restaurant_order_id END)';

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
        $collectedPayments = $this->collectedGuestPayments();
        $refundedPayments = $this->refundedGuestPayments();

        $payingGuests = (clone $collectedPayments)
            ->distinct()
            ->count(DB::raw(self::RESOLVED_GUEST_ID));

        $totalPaid = (float) (clone $collectedPayments)->sum('payments.amount');
        $refundTotal = (float) (clone $refundedPayments)->sum('payments.amount');

        $topGuests = (clone $collectedPayments)
            ->with('guest')
            ->selectRaw(self::RESOLVED_GUEST_ID.' as guest_id, SUM(payments.amount) as total_spend, COUNT(*) as payment_count')
            ->groupByRaw(self::RESOLVED_GUEST_ID)
            ->orderByDesc('total_spend')
            ->limit(5)
            ->get();

        $activity = [
            'hotel' => (clone $collectedPayments)->whereNotNull('payments.booking_id')->count(),
            'conference' => (clone $collectedPayments)->whereNotNull('payments.conference_booking_id')->count(),
            'table' => (clone $collectedPayments)->whereNotNull('payments.restaurant_reservation_id')->count(),
            'food' => (clone $collectedPayments)->whereNotNull('payments.restaurant_order_id')->count(),
        ];

        return [
            'totalGuests' => Guest::count(),
            'newGuests' => $this->forReportPeriod(Guest::query())->count(),
            'payingGuests' => $payingGuests,
            'returningGuests' => (clone $collectedPayments)
                ->selectRaw(self::RESOLVED_GUEST_ID.' as guest_id')
                ->groupByRaw(self::RESOLVED_GUEST_ID)
                ->havingRaw(self::DISTINCT_SERVICE_VISIT_COUNT.' >= 2')
                ->get()
                ->count(),
            'averageSpend' => $payingGuests > 0 ? $totalPaid / $payingGuests : 0,
            'totalPaid' => $totalPaid,
            'refundTotal' => $refundTotal,
            'refundCount' => (clone $refundedPayments)->count('payments.id'),
            'netSpend' => $totalPaid - $refundTotal,
            'paymentCount' => (clone $collectedPayments)->count('payments.id'),
            'activity' => $activity,
            'topGuests' => $topGuests,
        ];
    }

    /**
     * Builds gross guest collections in the period when funds were received.
     */
    private function collectedGuestPayments(): Builder
    {
        return $this->forReportPeriod(
            $this->guestPayments()
                ->whereIn('payments.payment_status', ['paid', 'completed', 'refunded', 'refund']),
            'payments.created_at',
        );
    }

    /**
     * Builds guest refund events in the period when each refund was processed.
     */
    private function refundedGuestPayments(): Builder
    {
        return $this->forReportPeriod(
            $this->guestPayments()->whereNotNull('payments.refunded_at'),
            'payments.refunded_at',
        );
    }

    /**
     * Resolves the guest through the same fallback order as Payment::transactionGuest().
     */
    private function guestPayments(): Builder
    {
        return Payment::query()
            ->leftJoin('bookings as guest_report_bookings', 'payments.booking_id', '=', 'guest_report_bookings.id')
            ->leftJoin('conference_bookings as guest_report_conferences', 'payments.conference_booking_id', '=', 'guest_report_conferences.id')
            ->leftJoin('restaurant_reservations as guest_report_reservations', 'payments.restaurant_reservation_id', '=', 'guest_report_reservations.id')
            ->leftJoin('restaurant_orders as guest_report_orders', 'payments.restaurant_order_id', '=', 'guest_report_orders.id')
            ->whereRaw(self::RESOLVED_GUEST_ID.' IS NOT NULL');
    }

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }
}
