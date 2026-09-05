<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboards\TransactionDashboard;
use App\Filament\Admin\Pages\GuestReport;
use App\Filament\Admin\Resources\Guests\GuestResource;
use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Filament\Admin\Widgets\GuestComparisonStats;
use App\Filament\Admin\Widgets\GuestStats;
use App\Filament\Admin\Widgets\GuestTrendChart;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\ConferenceRoom;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuestReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_report_uses_one_selected_period_for_structured_guest_data(): void
    {
        $reportPage = new GuestReport;
        $reportPage->period = 'yearly';

        self::assertSame('Yearly', $reportPage->periodLabel());

        $report = $reportPage->report();

        self::assertArrayHasKey('payingGuests', $report);
        self::assertArrayHasKey('returningGuests', $report);
        self::assertArrayHasKey('activity', $report);
        self::assertArrayHasKey('food', $report['activity']);
        self::assertArrayHasKey('other', $report['activity']);
    }

    public function test_guest_report_marks_a_selected_period_without_activity_as_empty(): void
    {
        $report = $this->reportForPeriod('2026-08-01', '2026-08-03');

        self::assertArrayHasKey('hasPeriodActivity', $report);
        self::assertFalse($report['hasPeriodActivity']);
    }

    public function test_a_new_guest_profile_marks_the_selected_period_as_active(): void
    {
        $this->createdAt(Guest::query()->create([
            'first_name' => 'New',
            'last_name' => 'Guest',
            'email' => 'new-period-guest@example.test',
            'phone_number' => '0240000011',
        ]), '2026-08-02 10:00:00');

        $report = $this->reportForPeriod('2026-08-01', '2026-08-03');

        self::assertArrayHasKey('hasPeriodActivity', $report);
        self::assertTrue($report['hasPeriodActivity']);
    }

    public function test_guest_report_builds_filtered_guest_and_payment_drill_downs_for_authorized_staff(): void
    {
        $permission = Permission::findOrCreate('view transaction dashboard', 'web');
        $role = Role::findOrCreate('super_admin', 'web');
        $role->givePermissionTo($permission);
        $superAdmin = User::factory()->create(['department' => 'admin']);
        $superAdmin->assignRole($role);
        $guest = Guest::query()->create([
            'first_name' => 'Drill',
            'last_name' => 'Down',
            'email' => 'drill-down@example.test',
            'phone_number' => '0240000014',
        ]);
        $this->actingAs($superAdmin);

        self::assertTrue(method_exists(GuestReport::class, 'drillDownUrls'));

        $page = new GuestReport;
        $page->period = 'custom';
        $page->startDate = '2026-08-10';
        $page->endDate = '2026-08-12';
        $urls = $page->drillDownUrls();

        self::assertSame('/admin/guests', parse_url($urls['guestProfiles'], PHP_URL_PATH));
        self::assertSame([
            'from' => '2026-08-10',
            'until' => '2026-08-12',
        ], $this->urlFilters($urls['newGuests'])['created_at']);
        self::assertNull($urls['returningGuests']);

        $payingFilters = $this->urlFilters($urls['payingGuests']);
        self::assertSame('/admin/payments', parse_url($urls['payingGuests'], PHP_URL_PATH));
        self::assertSame('revenue', $payingFilters['payment_status']);
        self::assertSame('created_at', $payingFilters['date_basis']);
        self::assertSame('2026-08-10', $payingFilters['start_date']);
        self::assertSame('2026-08-12', $payingFilters['end_date']);
        self::assertSame($urls['payingGuests'], $urls['averageSpend']);
        self::assertSame($urls['payingGuests'], $urls['grossSpend']);

        $refundFilters = $this->urlFilters($urls['refunds']);
        self::assertSame('refunded', $refundFilters['payment_status']);
        self::assertSame('refunded_at', $refundFilters['date_basis']);
        self::assertSame('hotel_bookings', $this->urlFilters($urls['activity']['hotel'])['transaction_type']);
        self::assertSame('conference_bookings', $this->urlFilters($urls['activity']['conference'])['transaction_type']);
        self::assertSame('table_reservations', $this->urlFilters($urls['activity']['table'])['transaction_type']);
        self::assertSame('food_orders', $this->urlFilters($urls['activity']['food'])['transaction_type']);
        self::assertSame('other', $this->urlFilters($urls['activity']['other'])['transaction_type']);
        self::assertSame('/admin/transaction-dashboard', parse_url($urls['netSpend'], PHP_URL_PATH));
        self::assertSame('collection-performance', parse_url($urls['netSpend'], PHP_URL_FRAGMENT));
        self::assertSame(
            GuestResource::getUrl('view', ['record' => $guest]),
            $page->guestDetailsUrl($guest),
        );
    }

    public function test_guest_report_uses_permission_safe_transaction_fallbacks_for_managers(): void
    {
        $permission = Permission::findOrCreate('view transaction dashboard', 'web');
        $role = Role::findOrCreate('manager', 'web');
        $role->givePermissionTo($permission);
        $manager = User::factory()->create(['department' => 'manager']);
        $manager->assignRole($role);
        $guest = Guest::query()->create([
            'first_name' => 'Manager',
            'last_name' => 'Guest',
            'email' => 'manager-guest@example.test',
            'phone_number' => '0240000015',
        ]);
        $this->actingAs($manager);

        self::assertFalse(GuestResource::canViewAny());
        self::assertFalse(PaymentResource::canViewAny());
        self::assertTrue(TransactionDashboard::canAccess());
        self::assertTrue(method_exists(GuestReport::class, 'drillDownUrls'));

        $page = new GuestReport;
        $page->period = 'custom';
        $page->startDate = '2026-08-10';
        $page->endDate = '2026-08-12';
        $urls = $page->drillDownUrls();

        self::assertNull($urls['guestProfiles']);
        self::assertNull($urls['newGuests']);
        self::assertNull($urls['returningGuests']);
        self::assertNull($page->guestDetailsUrl($guest));

        foreach ([
            $urls['payingGuests'],
            $urls['averageSpend'],
            $urls['grossSpend'],
            $urls['refunds'],
            $urls['netSpend'],
            ...array_values($urls['activity']),
        ] as $url) {
            self::assertSame('/admin/transaction-dashboard', parse_url($url, PHP_URL_PATH));
            self::assertSame('2026-08-10', $this->urlFilters($url)['start_date']);
            self::assertSame('2026-08-12', $this->urlFilters($url)['end_date']);
        }
    }

    public function test_guest_report_calculates_paid_guest_spend_for_the_selected_period(): void
    {
        $guest = Guest::query()->create([
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'phone_number' => '0240000000',
            'email' => 'ama@example.test',
        ]);

        Payment::query()->create([
            'guest_id' => $guest->id,
            'amount' => 125.50,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'GUEST-REPORT-TEST',
        ]);

        $report = (new GuestReport)->report();

        self::assertSame(1, $report['payingGuests']);
        self::assertSame(125.5, $report['averageSpend']);
        self::assertSame(125.5, $report['topGuests']->first()->total_spend);
        self::assertSame($guest->id, $report['topGuests']->first()->guest->id);
        self::assertTrue($report['hasPeriodActivity']);
    }

    public function test_guest_report_attributes_payments_through_every_supported_transaction_source(): void
    {
        [$guest, $room, $conferenceRoom, $restaurant, $table] = $this->serviceFixture();
        $hotel = Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-11',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $conference = ConferenceBooking::query()->create([
            'guest_id' => $guest->id,
            'conference_room_id' => $conferenceRoom->id,
            'booking_date' => '2026-09-10',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => 200,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $reservation = RestaurantReservation::query()->create([
            'restaurant_id' => $restaurant->id,
            'restaurant_table_id' => $table->id,
            'guest_id' => $guest->id,
            'guest_name' => $guest->full_name,
            'guest_email' => $guest->email,
            'guest_phone' => $guest->phone_number,
            'reservation_date' => '2026-09-10',
            'reservation_time' => '18:00',
            'number_of_guests' => 2,
            'reservation_fee' => 300,
            'status' => 'confirmed',
            'payment_status' => 'completed',
        ]);
        $foodOrder = RestaurantOrder::query()->create([
            'guest_id' => $guest->id,
            'order_number' => 'GUEST-REPORT-INDIRECT-ORDER',
            'total' => 400,
            'status' => 'confirmed',
            'payment_status' => 'completed',
        ]);

        foreach ([
            ['booking_id', $hotel->id, 100, 'GUEST-REPORT-INDIRECT-HOTEL'],
            ['conference_booking_id', $conference->id, 200, 'GUEST-REPORT-INDIRECT-CONFERENCE'],
            ['restaurant_reservation_id', $reservation->id, 300, 'GUEST-REPORT-INDIRECT-TABLE'],
            ['restaurant_order_id', $foodOrder->id, 400, 'GUEST-REPORT-INDIRECT-FOOD'],
        ] as [$foreignKey, $foreignId, $amount, $reference]) {
            Payment::query()->create([
                $foreignKey => $foreignId,
                'guest_id' => null,
                'amount' => $amount,
                'method' => 'cash',
                'payment_status' => 'completed',
                'transaction_reference' => $reference,
            ]);
        }

        Payment::query()->create([
            'guest_id' => null,
            'amount' => 999,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'GUEST-REPORT-TRULY-UNLINKED',
        ]);

        $report = (new GuestReport)->report();

        self::assertSame(1, $report['payingGuests']);
        self::assertSame(1000.0, $report['totalPaid']);
        self::assertSame(4, $report['paymentCount']);
        self::assertSame([
            'hotel' => 1,
            'conference' => 1,
            'table' => 1,
            'food' => 1,
            'other' => 0,
        ], $report['activity']);
        self::assertCount(1, $report['topGuests']);
        self::assertSame($guest->id, $report['topGuests']->first()->guest->id);
        self::assertEqualsWithDelta(1000.0, (float) $report['topGuests']->first()->total_spend, 0.001);
    }

    public function test_guest_report_builds_an_exclusive_service_mix_and_includes_direct_guest_payments(): void
    {
        [$guest, $room, $conferenceRoom] = $this->serviceFixture();
        $hotel = Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-11',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $conference = ConferenceBooking::query()->create([
            'guest_id' => $guest->id,
            'conference_room_id' => $conferenceRoom->id,
            'booking_date' => '2026-09-10',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        Payment::query()->create([
            'booking_id' => $hotel->id,
            'conference_booking_id' => $conference->id,
            'guest_id' => $guest->id,
            'amount' => 100,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'GUEST-REPORT-SERVICE-PRECEDENCE',
        ]);
        Payment::query()->create([
            'guest_id' => $guest->id,
            'amount' => 50,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'GUEST-REPORT-DIRECT-PAYMENT',
        ]);

        $report = (new GuestReport)->report();

        self::assertSame(2, $report['paymentCount']);
        self::assertSame([
            'hotel' => 1,
            'conference' => 0,
            'table' => 0,
            'food' => 0,
            'other' => 1,
        ], $report['activity']);
        self::assertSame(2, array_sum($report['activity']));
    }

    public function test_guest_report_prefers_the_payment_guest_over_the_source_transaction_guest(): void
    {
        [$sourceGuest, $room] = $this->serviceFixture();
        $paymentGuest = Guest::query()->create([
            'first_name' => 'Direct',
            'last_name' => 'Guest',
            'email' => 'direct-guest@example.test',
            'phone_number' => '0240000001',
        ]);
        $hotel = Booking::query()->create([
            'guest_id' => $sourceGuest->id,
            'room_id' => $room->id,
            'check_in' => '2026-09-12',
            'check_out' => '2026-09-13',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        Payment::query()->create([
            'booking_id' => $hotel->id,
            'guest_id' => $paymentGuest->id,
            'amount' => 100,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'GUEST-REPORT-DIRECT-PRIORITY',
        ]);

        $report = (new GuestReport)->report();

        self::assertSame(1, $report['payingGuests']);
        self::assertSame($paymentGuest->id, $report['topGuests']->first()->guest->id);
    }

    public function test_returning_guest_requires_two_distinct_paid_service_visits(): void
    {
        [$guest, $room] = $this->serviceFixture();
        $firstStay = Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-11',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $secondStay = Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-09-12',
            'check_out' => '2026-09-13',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        foreach ([40, 60] as $index => $amount) {
            Payment::query()->create([
                'booking_id' => $firstStay->id,
                'guest_id' => $guest->id,
                'amount' => $amount,
                'method' => 'cash',
                'payment_status' => 'completed',
                'transaction_reference' => 'GUEST-REPORT-INSTALMENT-'.$index,
            ]);
        }

        self::assertSame(0, (new GuestReport)->report()['returningGuests']);

        Payment::query()->create([
            'booking_id' => $secondStay->id,
            'guest_id' => $guest->id,
            'amount' => 100,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'GUEST-REPORT-SECOND-STAY',
        ]);

        self::assertSame(1, (new GuestReport)->report()['returningGuests']);
    }

    public function test_returning_guest_counts_service_types_separately_and_ignores_unlinked_payments(): void
    {
        [$returningGuest, $room, $conferenceRoom] = $this->serviceFixture();
        $directPaymentGuest = Guest::query()->create([
            'first_name' => 'Direct',
            'last_name' => 'Only',
            'email' => 'direct-only@example.test',
            'phone_number' => '0240000002',
        ]);
        $stay = Booking::query()->create([
            'guest_id' => $returningGuest->id,
            'room_id' => $room->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-11',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $conference = ConferenceBooking::query()->create([
            'guest_id' => $returningGuest->id,
            'conference_room_id' => $conferenceRoom->id,
            'booking_date' => '2026-09-10',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => 200,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        foreach ([
            ['booking_id', $stay->id, $returningGuest->id, 'GUEST-REPORT-VISIT-HOTEL'],
            ['conference_booking_id', $conference->id, $returningGuest->id, 'GUEST-REPORT-VISIT-CONFERENCE'],
            [null, null, $directPaymentGuest->id, 'GUEST-REPORT-DIRECT-ONE'],
            [null, null, $directPaymentGuest->id, 'GUEST-REPORT-DIRECT-TWO'],
        ] as [$foreignKey, $foreignId, $guestId, $reference]) {
            $payment = [
                'guest_id' => $guestId,
                'amount' => 100,
                'method' => 'cash',
                'payment_status' => 'completed',
                'transaction_reference' => $reference,
            ];

            if ($foreignKey !== null) {
                $payment[$foreignKey] = $foreignId;
            }

            Payment::query()->create($payment);
        }

        self::assertSame($stay->id, $conference->id);
        self::assertSame(1, (new GuestReport)->report()['returningGuests']);
    }

    public function test_later_refund_does_not_erase_guest_collection_from_original_period(): void
    {
        $guest = Guest::query()->create([
            'first_name' => 'Original',
            'last_name' => 'Collection',
            'email' => 'original-collection@example.test',
            'phone_number' => '0240000003',
        ]);
        $payment = Payment::query()->create([
            'guest_id' => $guest->id,
            'amount' => 250,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'GUEST-REPORT-LATER-REFUND',
        ]);
        $payment->forceFill([
            'payment_status' => 'refunded',
            'created_at' => Carbon::parse('2026-08-05 09:00:00'),
            'updated_at' => Carbon::parse('2026-08-20 12:00:00'),
            'refunded_at' => Carbon::parse('2026-08-20 12:00:00'),
        ])->saveQuietly();

        $report = $this->reportForPeriod('2026-08-01', '2026-08-10');

        self::assertSame(250.0, $report['totalPaid']);
        self::assertSame(0.0, $report['refundTotal']);
        self::assertSame(0, $report['refundCount']);
        self::assertSame(250.0, $report['netSpend']);
        self::assertSame(1, $report['payingGuests']);
        self::assertSame(1, $report['paymentCount']);
        self::assertEqualsWithDelta(250.0, (float) $report['topGuests']->first()->total_spend, 0.001);
    }

    public function test_refund_is_attributed_to_its_refunded_at_period(): void
    {
        $guest = Guest::query()->create([
            'first_name' => 'Refund',
            'last_name' => 'Period',
            'email' => 'refund-period@example.test',
            'phone_number' => '0240000004',
        ]);
        $payment = Payment::query()->create([
            'guest_id' => $guest->id,
            'amount' => 125,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'GUEST-REPORT-REFUND-PERIOD',
        ]);
        $payment->forceFill([
            'payment_status' => 'refunded',
            'created_at' => Carbon::parse('2026-07-15 09:00:00'),
            'updated_at' => Carbon::parse('2026-08-06 12:00:00'),
            'refunded_at' => Carbon::parse('2026-08-06 12:00:00'),
        ])->saveQuietly();

        $report = $this->reportForPeriod('2026-08-01', '2026-08-10');

        self::assertSame(0.0, $report['totalPaid']);
        self::assertSame(125.0, $report['refundTotal']);
        self::assertSame(1, $report['refundCount']);
        self::assertSame(-125.0, $report['netSpend']);
        self::assertSame(0, $report['payingGuests']);
        self::assertSame(0, $report['paymentCount']);
        self::assertCount(0, $report['topGuests']);
        self::assertTrue($report['hasPeriodActivity']);
    }

    public function test_guest_report_compares_the_previous_equal_length_period_and_fills_trend_gaps(): void
    {
        [$returningGuest, $room] = $this->serviceFixture();
        $this->createdAt($returningGuest, '2026-08-10 08:00:00');
        $payingGuest = $this->createdAt(Guest::query()->create([
            'first_name' => 'Current',
            'last_name' => 'Paying',
            'email' => 'current-paying@example.test',
            'phone_number' => '0240000007',
        ]), '2026-08-12 08:00:00');
        $previousGuest = $this->createdAt(Guest::query()->create([
            'first_name' => 'Previous',
            'last_name' => 'Paying',
            'email' => 'previous-paying@example.test',
            'phone_number' => '0240000008',
        ]), '2026-08-08 08:00:00');
        $firstStay = Booking::query()->create([
            'guest_id' => $returningGuest->id,
            'room_id' => $room->id,
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-11',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $secondStay = Booking::query()->create([
            'guest_id' => $returningGuest->id,
            'room_id' => $room->id,
            'check_in' => '2026-08-12',
            'check_out' => '2026-08-13',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        foreach ([$firstStay, $secondStay] as $index => $stay) {
            $this->createdAt(Payment::query()->create([
                'booking_id' => $stay->id,
                'guest_id' => $returningGuest->id,
                'amount' => 100,
                'method' => 'cash',
                'payment_status' => 'completed',
                'transaction_reference' => 'GUEST-TREND-RETURNING-'.$index,
            ]), '2026-08-10 '.($index === 0 ? '09:00:00' : '15:00:00'));
        }

        $this->createdAt(Payment::query()->create([
            'guest_id' => $payingGuest->id,
            'amount' => 75,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'GUEST-TREND-CURRENT-PAYING',
        ]), '2026-08-12 11:00:00');
        $this->createdAt(Payment::query()->create([
            'guest_id' => $previousGuest->id,
            'amount' => 50,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'GUEST-TREND-PREVIOUS-PAYING',
        ]), '2026-08-08 11:00:00');

        $report = $this->reportForPeriod('2026-08-10', '2026-08-12');

        self::assertArrayHasKey('comparison', $report);
        self::assertArrayHasKey('trend', $report);
        self::assertSame('Aug 7, 2026 to Aug 9, 2026', $report['comparison']['previousPeriodLabel']);
        self::assertSame([
            'current' => 2,
            'previous' => 1,
            'difference' => 1,
            'percentageChange' => 100.0,
        ], $report['comparison']['newGuests']);
        self::assertSame([
            'current' => 2,
            'previous' => 1,
            'difference' => 1,
            'percentageChange' => 100.0,
        ], $report['comparison']['payingGuests']);
        self::assertSame([
            'current' => 1,
            'previous' => 0,
            'difference' => 1,
            'percentageChange' => null,
        ], $report['comparison']['returningGuests']);
        self::assertSame([
            'granularity' => 'day',
            'labels' => ['Aug 10', 'Aug 11', 'Aug 12'],
            'newGuests' => [1, 0, 1],
            'payingGuests' => [1, 0, 1],
            'returningGuests' => [1, 0, 0],
        ], $report['trend']);
    }

    public function test_guest_trend_uses_readable_automatic_granularity_for_short_and_long_ranges(): void
    {
        $dailyReport = $this->reportForPeriod('2026-08-10', '2026-08-10');
        self::assertArrayHasKey('trend', $dailyReport);

        $daily = $dailyReport['trend'];
        $medium = $this->reportForPeriod('2026-08-01', '2026-09-15')['trend'];
        $long = $this->reportForPeriod('2024-01-01', '2026-01-01')['trend'];

        self::assertSame('hour', $daily['granularity']);
        self::assertCount(24, $daily['labels']);
        self::assertSame(['12 AM', '11 PM'], [$daily['labels'][0], $daily['labels'][23]]);
        self::assertSame('month', $medium['granularity']);
        self::assertSame(['Aug 2026', 'Sep 2026'], $medium['labels']);
        self::assertSame('year', $long['granularity']);
        self::assertSame(['2024', '2025', '2026'], $long['labels']);
    }

    public function test_guest_comparison_widget_uses_precomputed_values_without_queries(): void
    {
        self::assertTrue(class_exists(GuestComparisonStats::class));
        self::assertTrue(is_subclass_of(GuestComparisonStats::class, StatsOverviewWidget::class));

        $widget = new GuestComparisonStats;
        $widget->comparison = [
            'previousPeriodLabel' => 'Aug 7, 2026 to Aug 9, 2026',
            'newGuests' => ['current' => 2, 'previous' => 1, 'difference' => 1, 'percentageChange' => 100.0],
            'payingGuests' => ['current' => 1, 'previous' => 3, 'difference' => -2, 'percentageChange' => -66.7],
            'returningGuests' => ['current' => 0, 'previous' => 0, 'difference' => 0, 'percentageChange' => null],
        ];
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $stats = $method->invoke($widget);
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
        }

        self::assertCount(0, $queries);
        self::assertSame(
            ['New guest change', 'Paying guest change', 'Returning guest change'],
            array_map(fn ($stat): string => $stat->getLabel(), $stats),
        );
        self::assertSame(['+1', '-2', '0'], array_map(fn ($stat): string => $stat->getValue(), $stats));
        self::assertSame(['success', 'danger', 'gray'], array_map(
            fn ($stat): string|array|null => $stat->getColor(),
            $stats,
        ));
        self::assertStringContainsString('Current 2', $stats[0]->getDescription());
        self::assertStringContainsString('Previous 1', $stats[0]->getDescription());
        self::assertStringContainsString('Up 100.0%', $stats[0]->getDescription());
        self::assertStringContainsString('No previous-period baseline', $stats[2]->getDescription());
    }

    public function test_guest_trend_chart_uses_precomputed_values_and_accessible_count_options(): void
    {
        self::assertTrue(class_exists(GuestTrendChart::class));
        self::assertTrue(is_subclass_of(GuestTrendChart::class, ChartWidget::class));

        $widget = new GuestTrendChart;
        $widget->periodLabel = 'Aug 10, 2026 to Aug 12, 2026';
        $widget->trend = [
            'granularity' => 'day',
            'labels' => ['Aug 10', 'Aug 11', 'Aug 12'],
            'newGuests' => [1, 0, 1],
            'payingGuests' => [1, 0, 1],
            'returningGuests' => [1, 0, 0],
        ];
        $dataMethod = new \ReflectionMethod($widget, 'getData');
        $dataMethod->setAccessible(true);

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $data = $dataMethod->invoke($widget);
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
        }

        self::assertCount(0, $queries);
        self::assertSame(['Aug 10', 'Aug 11', 'Aug 12'], $data['labels']);
        self::assertSame(
            ['New guests', 'Paying guests', 'Returning guests'],
            array_column($data['datasets'], 'label'),
        );
        self::assertSame(
            ['#059669', '#2563EB', '#7C3AED'],
            array_column($data['datasets'], 'borderColor'),
        );
        self::assertFalse($widget->isEmpty());
        self::assertTrue((new GuestTrendChart)->isEmpty());

        $optionsMethod = new \ReflectionMethod($widget, 'getOptions');
        $optionsMethod->setAccessible(true);
        $options = $optionsMethod->invoke($widget);

        self::assertTrue($options['responsive']);
        self::assertSame(0, $options['scales']['y']['ticks']['precision']);
        self::assertSame('Guest count', $options['scales']['y']['title']['text']);
    }

    public function test_guest_report_uses_a_consolidated_query_budget(): void
    {
        $guest = Guest::query()->create([
            'first_name' => 'Query',
            'last_name' => 'Budget',
            'email' => 'query-budget@example.test',
            'phone_number' => '0240000005',
        ]);
        Payment::query()->create([
            'guest_id' => $guest->id,
            'amount' => 125,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'GUEST-REPORT-QUERY-BUDGET',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            (new GuestReport)->report();
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
        }

        self::assertCount(10, $queries);
    }

    public function test_guest_report_page_reuses_precomputed_metrics_in_the_stats_widget(): void
    {
        Role::findOrCreate('accountant', 'web');
        $accountant = User::factory()->create(['department' => 'accountant']);
        $accountant->assignRole('accountant');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $guest = Guest::query()->create([
            'first_name' => 'Page',
            'last_name' => 'Metrics',
            'email' => 'page-metrics@example.test',
            'phone_number' => '0240000006',
        ]);
        Payment::query()->create([
            'guest_id' => $guest->id,
            'amount' => 125,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'GUEST-REPORT-PAGE-METRICS',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $component = Livewire::actingAs($accountant)
                ->test(GuestReport::class)
                ->assertSuccessful();
            $reportQueries = collect(DB::getQueryLog())
                ->pluck('query')
                ->filter(fn (string $query): bool => (bool) preg_match(
                    '/\b(?:from|join)\s+["`]?(?:payments|guests|bookings|conference_bookings|restaurant_reservations|restaurant_orders)["`]?\b/i',
                    $query,
                ));
        } finally {
            DB::disableQueryLog();
        }

        self::assertCount(10, $reportQueries);
        self::assertStringContainsString('GHS 125.00', $component->html());
    }

    public function test_guest_report_marks_period_sensitive_results_as_busy_during_a_refresh(): void
    {
        Role::findOrCreate('accountant', 'web');
        $accountant = User::factory()->create(['department' => 'accountant']);
        $accountant->assignRole('accountant');
        Guest::query()->create([
            'first_name' => 'Busy',
            'last_name' => 'State',
            'email' => 'busy-state@example.test',
            'phone_number' => '0240000012',
        ]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $response = $this->actingAs($accountant)->get(GuestReport::getUrl());

        $response->assertOk();

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $selectedPeriod = $xpath->query('//section[@aria-label="Selected period guest results"]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $selectedPeriod);
        self::assertSame('aria-busy', $selectedPeriod->getAttribute('wire:loading.attr'));
        self::assertSame('applyReportPeriod,resetReportPeriod', $selectedPeriod->getAttribute('wire:target'));

        $results = $xpath->query('./div[@data-guest-period-results]', $selectedPeriod)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $results);
        self::assertStringContainsString('pointer-events-none', $results->getAttribute('wire:loading.class'));
        self::assertStringContainsString('opacity-60', $results->getAttribute('wire:loading.class'));
        self::assertStringContainsString('Guest spending', $results->textContent);
        self::assertStringContainsString('Top guests by gross spend', $results->textContent);
        self::assertStringNotContainsString('Report notes', $results->textContent);

        $status = $xpath->query('./div[@role="status"]', $selectedPeriod)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $status);
        self::assertSame('polite', $status->getAttribute('aria-live'));
        self::assertSame('true', $status->getAttribute('aria-atomic'));
        self::assertSame('applyReportPeriod,resetReportPeriod', $status->getAttribute('wire:target'));
        self::assertTrue($status->hasAttribute('wire:loading.flex'));
        self::assertStringContainsString('Updating guest report', $status->textContent);

        self::assertSame(0, $xpath->query('.//form[@*[name()="wire:submit"]="applyReportPeriod"]', $selectedPeriod)?->count());
    }

    public function test_guest_report_places_comparison_and_trend_before_detailed_guest_sections(): void
    {
        Role::findOrCreate('accountant', 'web');
        $accountant = User::factory()->create(['department' => 'accountant']);
        $accountant->assignRole('accountant');
        Guest::query()->create([
            'first_name' => 'Report',
            'last_name' => 'Activity',
            'email' => 'report-activity@example.test',
            'phone_number' => '0240000013',
        ]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $response = $this->actingAs($accountant)->get(GuestReport::getUrl());

        $response->assertOk()->assertSeeInOrder([
            'Guest profiles',
            'Previous-period comparison',
            'Guest trend',
            'Guest spending',
            'Paid service mix',
            'Top guests by gross spend',
        ]);

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $results = $xpath->query('//div[@data-guest-period-results]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $results);
        self::assertCount(1, $xpath->query('.//section[@aria-label="Previous-period guest comparison"]', $results));
        self::assertCount(1, $xpath->query('.//section[@aria-label="Guest trend chart"]', $results));
    }

    public function test_guest_report_separates_the_all_time_guest_base_from_selected_period_metrics(): void
    {
        Role::findOrCreate('accountant', 'web');
        $accountant = User::factory()->create(['department' => 'accountant']);
        $accountant->assignRole('accountant');
        foreach (range(1, 3) as $index) {
            Guest::query()->create([
                'first_name' => 'Guest',
                'last_name' => (string) $index,
                'phone_number' => '024000000'.$index,
                'email' => 'overview-guest-'.$index.'@example.test',
            ]);
        }
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $response = $this->actingAs($accountant)->get(GuestReport::getUrl());

        $response->assertOk();

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $overview = $xpath->query('//section[@aria-labelledby="guest-overview-heading"]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $overview);
        self::assertStringContainsString('Selected-period overview', $overview->textContent);

        $guestBase = $xpath->query('.//*[@role="note" and @aria-label="All-time guest base"]', $overview)?->item(0);

        self::assertInstanceOf(\DOMElement::class, $guestBase);
        self::assertStringContainsString('Guest profiles', $guestBase->textContent);
        self::assertStringContainsString('3', $guestBase->textContent);
    }

    public function test_top_guests_render_as_mobile_cards_and_a_desktop_table(): void
    {
        Role::findOrCreate('accountant', 'web');
        $accountant = User::factory()->create(['department' => 'accountant']);
        $accountant->assignRole('accountant');
        $guest = Guest::query()->create([
            'first_name' => 'Akosua',
            'last_name' => 'Mensah-Danquah',
            'phone_number' => '0240000010',
            'email' => 'akosua.mensah-danquah@example.test',
        ]);
        Payment::query()->create([
            'guest_id' => $guest->id,
            'amount' => 275.50,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'GUEST-MOBILE-TOP-1',
        ]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $response = $this->actingAs($accountant)->get(GuestReport::getUrl());

        $response->assertOk();

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $mobileList = $xpath->query('//*[@aria-label="Top guests mobile list"]')?->item(0);
        $desktopTable = $xpath->query('//*[@aria-label="Top guests desktop table"]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $mobileList);
        self::assertStringContainsString('md:hidden', $mobileList->getAttribute('class'));
        self::assertCount(1, $xpath->query('./li', $mobileList));
        self::assertStringContainsString('Akosua Mensah-Danquah', $mobileList->textContent);
        self::assertStringContainsString('akosua.mensah-danquah@example.test', $mobileList->textContent);
        self::assertStringContainsString('GHS 275.50', $mobileList->textContent);

        self::assertInstanceOf(\DOMElement::class, $desktopTable);
        self::assertStringContainsString('hidden', $desktopTable->getAttribute('class'));
        self::assertStringContainsString('md:block', $desktopTable->getAttribute('class'));
        self::assertCount(1, $xpath->query('.//tbody/tr', $desktopTable));
    }

    public function test_guest_report_renders_authorized_drill_down_links_without_linking_returning_guests(): void
    {
        $permission = Permission::findOrCreate('view transaction dashboard', 'web');
        $role = Role::findOrCreate('super_admin', 'web');
        $role->givePermissionTo($permission);
        $superAdmin = User::factory()->create(['department' => 'admin']);
        $superAdmin->assignRole($role);
        $guest = Guest::query()->create([
            'first_name' => 'Linked',
            'last_name' => 'Guest',
            'email' => 'linked-guest@example.test',
            'phone_number' => '0240000016',
        ]);
        Payment::query()->create([
            'guest_id' => $guest->id,
            'amount' => 325,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'GUEST-REPORT-DRILL-DOWN',
        ]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($superAdmin);
        $urls = (new GuestReport)->drillDownUrls();

        $response = $this->get(GuestReport::getUrl());

        $response->assertOk();

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $results = $xpath->query('//section[@aria-label="Selected period guest results"]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $results);
        self::assertSame(
            $urls['guestProfiles'],
            $xpath->query('.//a[@aria-label="View all guest profiles"]', $results)?->item(0)?->getAttribute('href'),
        );
        self::assertSame(
            $urls['newGuests'],
            $xpath->query('.//a[contains(normalize-space(.), "New guests")]', $results)?->item(0)?->getAttribute('href'),
        );
        self::assertSame(
            $urls['payingGuests'],
            $xpath->query('.//a[contains(normalize-space(.), "Paying guests")]', $results)?->item(0)?->getAttribute('href'),
        );
        self::assertSame(
            $urls['averageSpend'],
            $xpath->query('.//a[contains(normalize-space(.), "Average gross spend")]', $results)?->item(0)?->getAttribute('href'),
        );
        self::assertCount(0, $xpath->query('.//a[contains(normalize-space(.), "Returning guests")]', $results));
        self::assertSame(
            $urls['grossSpend'],
            $xpath->query('.//a[@aria-label="View gross guest spend payments"]', $results)?->item(0)?->getAttribute('href'),
        );
        self::assertSame(
            $urls['refunds'],
            $xpath->query('.//a[@aria-label="View guest refund payments"]', $results)?->item(0)?->getAttribute('href'),
        );
        self::assertSame(
            $urls['netSpend'],
            $xpath->query('.//a[@aria-label="View net guest spend analysis"]', $results)?->item(0)?->getAttribute('href'),
        );

        foreach (['hotel', 'conference', 'table', 'food', 'other'] as $type) {
            self::assertSame(
                $urls['activity'][$type],
                $xpath->query('.//a[@aria-label="View '.$type.' guest payment activity"]', $results)?->item(0)?->getAttribute('href'),
            );
        }

        self::assertCount(2, $xpath->query('.//a[@aria-label="View guest Linked Guest"]', $results));
    }

    public function test_guest_report_renders_paid_service_mix_with_relative_contribution(): void
    {
        Role::findOrCreate('accountant', 'web');
        $accountant = User::factory()->create(['department' => 'accountant']);
        $accountant->assignRole('accountant');
        $guest = Guest::query()->create([
            'first_name' => 'Service',
            'last_name' => 'Mix',
            'email' => 'service-mix@example.test',
            'phone_number' => '0240000017',
        ]);
        foreach ([125, 75] as $index => $amount) {
            Payment::query()->create([
                'guest_id' => $guest->id,
                'amount' => $amount,
                'method' => 'cash',
                'payment_status' => 'completed',
                'transaction_reference' => 'GUEST-REPORT-SERVICE-MIX-'.$index,
            ]);
        }
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $response = $this->actingAs($accountant)->get(GuestReport::getUrl());

        $response->assertOk();

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $serviceMix = $xpath->query('//section[@aria-label="Paid service mix"]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $serviceMix);
        self::assertStringContainsString('2 collected payments', $serviceMix->textContent);
        self::assertStringNotContainsString('Returning guests', $serviceMix->textContent);
        self::assertCount(5, $xpath->query('.//*[@data-service-mix-channel]', $serviceMix));

        $other = $xpath->query('.//*[@data-service-mix-channel="other"]', $serviceMix)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $other);
        self::assertStringContainsString('Other / direct', $other->textContent);
        self::assertStringContainsString('2 payments', $other->textContent);
        self::assertStringContainsString('100.0%', $other->textContent);

        $otherShare = $xpath->query('.//*[@role="progressbar"]', $other)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $otherShare);
        self::assertSame('100', $otherShare->getAttribute('aria-valuenow'));
    }

    public function test_guest_report_service_mix_uses_a_focused_empty_state_without_zero_rows(): void
    {
        Role::findOrCreate('accountant', 'web');
        $accountant = User::factory()->create(['department' => 'accountant']);
        $accountant->assignRole('accountant');
        Guest::query()->create([
            'first_name' => 'New',
            'last_name' => 'Only',
            'email' => 'new-only@example.test',
            'phone_number' => '0240000018',
        ]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $response = $this->actingAs($accountant)->get(GuestReport::getUrl());

        $response->assertOk();

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $serviceMix = $xpath->query('//section[@aria-label="Paid service mix"]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $serviceMix);
        self::assertStringContainsString('No collected payment activity', $serviceMix->textContent);
        self::assertCount(0, $xpath->query('.//*[@data-service-mix-channel]', $serviceMix));
        self::assertCount(0, $xpath->query('.//*[@role="progressbar"]', $serviceMix));
    }

    public function test_an_empty_selected_period_replaces_zero_heavy_sections_with_one_actionable_state(): void
    {
        Role::findOrCreate('accountant', 'web');
        $accountant = User::factory()->create(['department' => 'accountant']);
        $accountant->assignRole('accountant');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $response = $this->actingAs($accountant)->get(GuestReport::getUrl());

        $response->assertOk();

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);

        $emptyState = $xpath->query('//*[@role="status" and @aria-label="No guest activity for selected period"]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $emptyState);
        self::assertStringContainsString('No guest activity in this period', $emptyState->textContent);
        self::assertStringContainsString('Change reporting period', $emptyState->textContent);
        self::assertCount(1, $xpath->query('//*[@id="guest-report-period-controls"]'));
        self::assertCount(1, $xpath->query('//*[@role="note" and @aria-label="All-time guest base"]'));
        self::assertCount(0, $xpath->query('//*[@role="status" and @aria-label="No top guests"]'));
        self::assertCount(0, $xpath->query('//*[@aria-label="Top guests mobile list"]'));
        self::assertCount(0, $xpath->query('//*[@aria-label="Top guests desktop table"]'));
        self::assertStringNotContainsString('Guest spending', $response->getContent());
        self::assertStringNotContainsString('Previous-period comparison', $response->getContent());
    }

    public function test_guest_stats_widget_uses_precomputed_period_aware_data_without_queries(): void
    {
        self::assertTrue(is_subclass_of(GuestStats::class, StatsOverviewWidget::class));
        self::assertTrue(property_exists(GuestStats::class, 'drillDownUrls'));

        $widget = new GuestStats;
        $widget->reportData = [
            'newGuests' => 4,
            'payingGuests' => 3,
            'returningGuests' => 2,
            'averageSpend' => 1250.5,
        ];
        $widget->reportPeriodLabel = 'Yearly';
        $widget->drillDownUrls = [
            'newGuests' => '/admin/guests?joined=yearly',
            'payingGuests' => '/admin/payments?scope=guests',
            'returningGuests' => null,
            'averageSpend' => '/admin/payments?scope=guests',
        ];
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $stats = $method->invoke($widget);
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
        }

        self::assertCount(0, $queries);
        self::assertCount(4, $stats);
        self::assertSame(
            ['4', '3', '2', 'GHS 1,250.50'],
            array_map(fn ($stat): string => $stat->getValue(), $stats),
        );
        self::assertSame('Profiles created in Yearly', $stats[0]->getDescription());
        self::assertSame('Gross collections per paying guest in Yearly', $stats[3]->getDescription());
        self::assertSame('/admin/guests?joined=yearly', $stats[0]->getUrl());
        self::assertSame('/admin/payments?scope=guests', $stats[1]->getUrl());
        self::assertNull($stats[2]->getUrl());
        self::assertSame('/admin/payments?scope=guests', $stats[3]->getUrl());

        $columns = new \ReflectionMethod($widget, 'getColumns');
        $columns->setAccessible(true);

        self::assertSame([
            'default' => 1,
            'sm' => 2,
            'xl' => 4,
        ], $columns->invoke($widget));
    }

    /**
     * @return array{0: Guest, 1: Room, 2: ConferenceRoom, 3: Restaurant, 4: RestaurantTable}
     */
    private function serviceFixture(): array
    {
        $guest = Guest::query()->create([
            'first_name' => 'Guest',
            'last_name' => 'Attribution',
            'email' => 'guest-attribution@example.test',
            'phone_number' => '0240000000',
        ]);
        $roomType = RoomType::query()->create([
            'name' => 'Guest Report Room Type',
            'price_per_night' => 100,
            'capacity' => 2,
        ]);
        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'GUEST-REPORT-101',
            'status' => 'available',
        ]);
        $conferenceRoom = ConferenceRoom::query()->create([
            'name' => 'Guest Report Conference Room',
            'capacity' => 20,
            'price_per_hour' => 100,
            'is_available' => true,
        ]);
        $restaurant = Restaurant::query()->create([
            'name' => 'Guest Report Restaurant',
            'description' => 'Restaurant fixture for guest attribution tests.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $table = RestaurantTable::query()->create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'GUEST-REPORT-T1',
            'capacity' => 4,
        ]);

        return [$guest, $room, $conferenceRoom, $restaurant, $table];
    }

    private function reportForPeriod(string $startDate, string $endDate): array
    {
        $page = new GuestReport;
        $page->period = 'custom';
        $page->startDate = $startDate;
        $page->endDate = $endDate;

        return $page->report();
    }

    /**
     * @return array<string, mixed>
     */
    private function urlFilters(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return $query['filters'] ?? [];
    }

    /**
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @return TModel
     */
    private function createdAt(Model $model, string $createdAt): Model
    {
        $model->forceFill([
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ])->saveQuietly();

        return $model;
    }
}
