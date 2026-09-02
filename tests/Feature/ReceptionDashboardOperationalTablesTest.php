<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Filament\Admin\Widgets\ReceptionArrivals;
use App\Filament\Admin\Widgets\ReceptionDepartures;
use App\Filament\Admin\Widgets\ReceptionDeskStats;
use App\Filament\Admin\Widgets\ReceptionStats;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class ReceptionDashboardOperationalTablesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_reception_aggregate_stats_do_not_poll_automatically(): void
    {
        foreach ([ReceptionDeskStats::class, ReceptionStats::class] as $widgetClass) {
            $method = new ReflectionMethod($widgetClass, 'getPollingInterval');
            $method->setAccessible(true);

            self::assertNull($method->invoke(new $widgetClass), $widgetClass);
        }
    }

    public function test_reception_queues_poll_only_when_the_selected_period_includes_today(): void
    {
        Carbon::setTestNow('2026-08-10 09:00:00');

        foreach ([ReceptionArrivals::class, ReceptionDepartures::class] as $widgetClass) {
            self::assertSame('30s', $this->table($widgetClass, '2026-08-10', '2026-08-12')->getPollingInterval());
            self::assertNull($this->table($widgetClass, '2026-08-01', '2026-08-09')->getPollingInterval());
            self::assertNull($this->table($widgetClass, '2026-08-11', '2026-08-12')->getPollingInterval());
        }
    }

    public function test_reception_queues_keep_primary_information_visible_on_mobile(): void
    {
        foreach ([ReceptionArrivals::class, ReceptionDepartures::class] as $widgetClass) {
            $table = $this->table($widgetClass);

            foreach (['guest.first_name', 'room.room_number', 'status'] as $columnName) {
                self::assertNull($table->getColumn($columnName)?->getVisibleFrom(), "[{$columnName}] should remain visible on mobile.");
            }

            foreach (['payment_status' => 'md', 'room.roomType.name' => 'lg'] as $columnName => $breakpoint) {
                $column = $table->getColumn($columnName);

                self::assertSame($breakpoint, $column?->getVisibleFrom(), "[{$columnName}] has the wrong responsive breakpoint.");
                self::assertTrue($column?->isToggleable(), "[{$columnName}] should be user-toggleable.");
            }
        }

        foreach ([
            ReceptionArrivals::class => ['check_in', 'check_in_time'],
            ReceptionDepartures::class => ['check_out', 'check_out_time'],
        ] as $widgetClass => $scheduleColumns) {
            $table = $this->table($widgetClass);

            foreach ($scheduleColumns as $columnName) {
                $column = $table->getColumn($columnName);

                self::assertSame('md', $column?->getVisibleFrom(), "[{$columnName}] should collapse on mobile.");
                self::assertTrue($column?->isToggleable(), "[{$columnName}] should be user-toggleable.");
            }
        }
    }

    public function test_reception_queue_schedules_are_readable_on_mobile_and_in_desktop_columns(): void
    {
        [$guest, $room] = $this->hotelFixture('Schedule', 'Guest');
        $booking = $this->booking($guest, $room, '2026-08-10', '2026-08-12', '14:30:00', '09:15:00');

        foreach ([
            ReceptionArrivals::class => ['check_in_time', '2:30 PM', 'Arrives Aug 10, 2026 at 2:30 PM'],
            ReceptionDepartures::class => ['check_out_time', '9:15 AM', 'Departs Aug 12, 2026 at 9:15 AM'],
        ] as $widgetClass => [$timeColumnName, $formattedTime, $mobileDescription]) {
            $table = $this->table($widgetClass);
            $timeColumn = $table->getColumn($timeColumnName);
            $guestColumn = $table->getColumn('guest.first_name');

            self::assertInstanceOf(TextColumn::class, $timeColumn);
            self::assertSame($formattedTime, $timeColumn->formatState($booking->{$timeColumnName}));

            $guestColumn?->record($booking)->clearCachedState();
            self::assertSame($mobileDescription, $guestColumn?->getDescriptionBelow());
        }
    }

    public function test_reception_queue_guest_search_matches_first_and_last_names(): void
    {
        [$targetGuest, $room] = $this->hotelFixture('Kwame', 'NeedleSurname');
        [$otherGuest] = $this->hotelFixture('DifferentFirst', 'DifferentSurname');
        $target = $this->booking($targetGuest, $room);
        $this->booking($otherGuest, $room);

        foreach ([ReceptionArrivals::class, ReceptionDepartures::class] as $widgetClass) {
            $table = $this->table($widgetClass, '2026-08-01', '2026-08-31');
            $query = clone $table->getQuery();
            $isFirst = true;

            $table->getColumn('guest.first_name')?->applySearchConstraint($query, 'NeedleSurname', $isFirst);

            self::assertSame([$target->id], $query->pluck('bookings.id')->all(), $widgetClass);
        }
    }

    public function test_reception_queues_have_tailored_empty_states_and_booking_details_actions(): void
    {
        [$guest, $room] = $this->hotelFixture('Action', 'Guest');
        $booking = $this->booking($guest, $room);

        foreach ([
            ReceptionArrivals::class => 'No arrivals in this period',
            ReceptionDepartures::class => 'No departures in this period',
        ] as $widgetClass => $emptyHeading) {
            $table = $this->table($widgetClass);

            self::assertSame($emptyHeading, $table->getEmptyStateHeading());
            self::assertNotEmpty($table->getEmptyStateDescription());
            self::assertSame('heroicon-o-calendar-days', $table->getEmptyStateIcon());
            self::assertSame(10, $table->getDefaultPaginationPageOption());

            $action = $table->getAction('details');

            self::assertInstanceOf(Action::class, $action);
            $action->record($booking);
            self::assertSame('Details', $action->getLabel());
            self::assertSame('heroicon-o-eye', $action->getIcon());
            self::assertSame(
                parse_url(BookingResource::getUrl('view', ['record' => $booking]), PHP_URL_PATH),
                parse_url((string) $action->getUrl(), PHP_URL_PATH),
            );
        }
    }

    public function test_reception_queue_badges_use_readable_labels_and_semantic_colors(): void
    {
        foreach ([ReceptionArrivals::class, ReceptionDepartures::class] as $widgetClass) {
            $table = $this->table($widgetClass);
            $payment = $table->getColumn('payment_status');
            $status = $table->getColumn('status');

            self::assertTrue($payment?->isBadge());
            self::assertSame('Partially Paid', $payment?->formatState('partially_paid'));
            self::assertSame('success', $payment?->getColor('paid'));
            self::assertSame('warning', $payment?->getColor('partial'));
            self::assertSame('danger', $payment?->getColor('unpaid'));
            self::assertSame('info', $payment?->getColor('refunded'));

            self::assertTrue($status?->isBadge());
            self::assertSame('Checked In', $status?->formatState('checked_in'));
            self::assertSame('warning', $status?->getColor('pending'));
            self::assertSame('info', $status?->getColor('confirmed'));
            self::assertSame('success', $status?->getColor('checked_in'));
            self::assertSame('danger', $status?->getColor('no_show'));
        }
    }

    /**
     * @param  class-string<ReceptionArrivals|ReceptionDepartures>  $widgetClass
     */
    private function table(
        string $widgetClass,
        string $startDate = '2026-08-01',
        string $endDate = '2026-08-31',
    ): Table {
        $widget = new $widgetClass;
        $widget->pageFilters = [
            'period' => 'custom',
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        return $widget->table(Table::make($this->createMock(HasTable::class)));
    }

    /**
     * @return array{Guest, Room}
     */
    private function hotelFixture(string $firstName, string $lastName): array
    {
        $guest = Guest::query()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower($firstName.'.'.$lastName.'.'.str()->random(6)).'@example.test',
            'phone_number' => '024'.random_int(1000000, 9999999),
        ]);
        $roomType = RoomType::query()->firstOrCreate([
            'name' => 'Reception Operations Room',
        ], [
            'price_per_night' => 100,
            'capacity' => 2,
        ]);
        $room = Room::query()->firstOrCreate([
            'room_number' => 'RECEPTION-OPS-101',
        ], [
            'room_type_id' => $roomType->id,
            'status' => 'available',
        ]);

        return [$guest, $room];
    }

    private function booking(
        Guest $guest,
        Room $room,
        string $checkIn = '2026-08-10',
        string $checkOut = '2026-08-12',
        ?string $checkInTime = '14:00:00',
        ?string $checkOutTime = '10:00:00',
    ): Booking {
        return Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
            'total_price' => 200,
            'status' => 'confirmed',
            'payment_status' => 'partial',
        ]);
    }
}
