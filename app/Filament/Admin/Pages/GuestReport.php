<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\InteractsWithReportPeriod;
use App\Models\Guest;
use App\Models\Payment;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

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
        [$periodStart, $periodEnd] = $this->periodBounds();
        $collectedPayments = $this->collectedGuestPayments();
        $refundedPayments = $this->refundedGuestPayments();

        $guestSummary = Guest::query()
            ->selectRaw('COUNT(*) as total_guests')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN guests.created_at BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) as new_guests',
                [$periodStart, $periodEnd],
            )
            ->first();

        $collectedSummary = (clone $collectedPayments)
            ->selectRaw('COUNT(DISTINCT '.self::RESOLVED_GUEST_ID.') as paying_guests')
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as total_paid')
            ->selectRaw('COUNT(payments.id) as payment_count')
            ->selectRaw('COUNT(payments.booking_id) as hotel_payment_count')
            ->selectRaw('COUNT(payments.conference_booking_id) as conference_payment_count')
            ->selectRaw('COUNT(payments.restaurant_reservation_id) as table_payment_count')
            ->selectRaw('COUNT(payments.restaurant_order_id) as food_payment_count')
            ->first();

        $refundSummary = (clone $refundedPayments)
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as refund_total')
            ->selectRaw('COUNT(payments.id) as refund_count')
            ->first();

        $payingGuests = (int) $collectedSummary->paying_guests;
        $totalPaid = (float) $collectedSummary->total_paid;
        $refundTotal = (float) $refundSummary->refund_total;

        $topGuests = (clone $collectedPayments)
            ->with('guest')
            ->selectRaw(self::RESOLVED_GUEST_ID.' as guest_id, SUM(payments.amount) as total_spend, COUNT(*) as payment_count')
            ->groupByRaw(self::RESOLVED_GUEST_ID)
            ->orderByDesc('total_spend')
            ->limit(5)
            ->get();

        return [
            'totalGuests' => (int) $guestSummary->total_guests,
            'newGuests' => (int) $guestSummary->new_guests,
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
            'refundCount' => (int) $refundSummary->refund_count,
            'netSpend' => $totalPaid - $refundTotal,
            'paymentCount' => (int) $collectedSummary->payment_count,
            'activity' => [
                'hotel' => (int) $collectedSummary->hotel_payment_count,
                'conference' => (int) $collectedSummary->conference_payment_count,
                'table' => (int) $collectedSummary->table_payment_count,
                'food' => (int) $collectedSummary->food_payment_count,
            ],
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
