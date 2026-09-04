<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\GuestReport;
use App\Filament\Admin\Widgets\GuestStats;
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
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
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
        ], $report['activity']);
        self::assertCount(1, $report['topGuests']);
        self::assertSame($guest->id, $report['topGuests']->first()->guest->id);
        self::assertEqualsWithDelta(1000.0, (float) $report['topGuests']->first()->total_spend, 0.001);
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

        self::assertCount(6, $queries);
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

        self::assertCount(6, $reportQueries);
        self::assertSame(4, substr_count($component->html(), 'GHS 125.00'));
    }

    public function test_guest_report_marks_period_sensitive_results_as_busy_during_a_refresh(): void
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

    public function test_guest_stats_widget_uses_precomputed_period_aware_data_without_queries(): void
    {
        self::assertTrue(is_subclass_of(GuestStats::class, StatsOverviewWidget::class));

        $widget = new GuestStats;
        $widget->reportData = [
            'totalGuests' => 25,
            'newGuests' => 4,
            'payingGuests' => 3,
            'returningGuests' => 2,
            'averageSpend' => 1250.5,
        ];
        $widget->reportPeriodLabel = 'Yearly';
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
        self::assertCount(5, $stats);
        self::assertSame(
            ['25', '4', '3', '2', 'GHS 1,250.50'],
            array_map(fn ($stat): string => $stat->getValue(), $stats),
        );
        self::assertSame('Profiles created in Yearly', $stats[1]->getDescription());
        self::assertSame('Gross collections per paying guest in Yearly', $stats[4]->getDescription());
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
}
