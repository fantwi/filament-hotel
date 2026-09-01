<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Admin\Resources\Bookings\Pages\EditBooking;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\RoomAvailabilityService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class AdminBookingAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_availability_excludes_maintenance_and_active_overlapping_bookings(): void
    {
        $fixtures = $this->makeFixtures();
        $dates = $this->futureDates();

        $maintenance = $this->makeRoom($fixtures['roomType'], 'M-101', 'maintenance');
        $confirmed = $this->makeRoom($fixtures['roomType'], 'C-101');
        $cancelled = $this->makeRoom($fixtures['roomType'], 'X-101');
        $noShow = $this->makeRoom($fixtures['roomType'], 'N-101');
        $expiredHold = $this->makeRoom($fixtures['roomType'], 'E-101');

        $this->makeBooking($fixtures['guest'], $confirmed, $dates['checkIn'], $dates['checkOut'], 'pending');
        $this->makeBooking($fixtures['guest'], $cancelled, $dates['checkIn'], $dates['checkOut'], 'cancelled');
        $this->makeBooking($fixtures['guest'], $noShow, $dates['checkIn'], $dates['checkOut'], 'no_show');
        $this->makeBooking($fixtures['guest'], $expiredHold, $dates['checkIn'], $dates['checkOut'], 'pending', 'expired');

        $availableRoomIds = app(RoomAvailabilityService::class)
            ->query($dates['checkIn'], $dates['checkOut'])
            ->pluck('id')
            ->all();

        self::assertNotContains($maintenance->id, $availableRoomIds);
        self::assertNotContains($confirmed->id, $availableRoomIds);
        self::assertContains($cancelled->id, $availableRoomIds);
        self::assertContains($noShow->id, $availableRoomIds);
        self::assertContains($expiredHold->id, $availableRoomIds);
    }

    public function test_availability_allows_adjacent_stays_and_the_booking_being_edited(): void
    {
        $fixtures = $this->makeFixtures();
        $room = $this->makeRoom($fixtures['roomType'], '101');
        $checkIn = Carbon::today()->addDays(10);
        $checkOut = $checkIn->copy()->addDays(2);

        $booking = $this->makeBooking($fixtures['guest'], $room, $checkIn, $checkOut, 'pending');
        $availability = app(RoomAvailabilityService::class);

        self::assertTrue($availability->isAvailable($room->id, $checkOut, $checkOut->copy()->addDays(2)));
        self::assertTrue($availability->isAvailable($room->id, $checkIn, $checkOut, $booking->id));
        self::assertFalse($availability->isAvailable($room->id, $checkIn, $checkOut));
    }

    public function test_create_form_rejects_past_equal_and_reversed_date_ranges(): void
    {
        $fixtures = $this->makeFixtures();
        $room = $this->makeRoom($fixtures['roomType'], '101');
        $today = Carbon::today();

        $this->createBookingPage($fixtures['admin'])
            ->fillForm($this->formData($fixtures['guest'], $room, $today->copy()->subDay(), $today->copy()->addDay()))
            ->call('create')
            ->assertHasFormErrors(['check_in']);

        $this->createBookingPage($fixtures['admin'])
            ->fillForm($this->formData($fixtures['guest'], $room, $today, $today))
            ->call('create')
            ->assertHasFormErrors(['check_out']);

        $this->createBookingPage($fixtures['admin'])
            ->fillForm($this->formData($fixtures['guest'], $room, $today->copy()->addDays(2), $today->copy()->addDay()))
            ->call('create')
            ->assertHasFormErrors(['check_out']);
    }

    public function test_create_form_blocks_maintenance_and_conflicting_rooms_but_accepts_an_adjacent_stay(): void
    {
        $fixtures = $this->makeFixtures();
        $dates = $this->futureDates();
        $maintenance = $this->makeRoom($fixtures['roomType'], 'M-101', 'maintenance');
        $occupied = $this->makeRoom($fixtures['roomType'], 'O-101');
        $adjacent = $this->makeRoom($fixtures['roomType'], 'A-101');

        $this->makeBooking($fixtures['guest'], $occupied, $dates['checkIn'], $dates['checkOut'], 'pending');
        $this->makeBooking($fixtures['guest'], $adjacent, $dates['checkIn'], $dates['checkOut'], 'pending');

        $this->createBookingPage($fixtures['admin'])
            ->fillForm($this->formData($fixtures['guest'], $maintenance, $dates['checkIn'], $dates['checkOut']))
            ->call('create')
            ->assertHasFormErrors(['room_id']);

        $this->createBookingPage($fixtures['admin'])
            ->fillForm($this->formData($fixtures['guest'], $occupied, $dates['checkIn'], $dates['checkOut']))
            ->call('create')
            ->assertHasFormErrors(['room_id']);

        $this->createBookingPage($fixtures['admin'])
            ->fillForm($this->formData($fixtures['guest'], $adjacent, $dates['checkOut'], $dates['checkOut']->copy()->addDay()))
            ->assertFormSet(['room_id' => $adjacent->id])
            ->call('create')
            ->assertHasNoFormErrors();

        self::assertSame(150.0, (float) Booking::query()
            ->where('room_id', $adjacent->id)
            ->whereDate('check_in', $dates['checkOut'])
            ->sole()
            ->total_price);
    }

    public function test_edit_form_does_not_treat_the_current_booking_as_a_conflict(): void
    {
        $fixtures = $this->makeFixtures();
        $dates = $this->futureDates();
        $room = $this->makeRoom($fixtures['roomType'], '101');
        $booking = $this->makeBooking($fixtures['guest'], $room, $dates['checkIn'], $dates['checkOut'], 'pending');

        $this->editBookingPage($fixtures['admin'], $booking)
            ->fillForm($this->formData($fixtures['guest'], $room, $dates['checkIn'], $dates['checkOut']))
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_create_form_recalculates_a_tampered_total_from_the_selected_room_and_stay(): void
    {
        $fixtures = $this->makeFixtures();
        $dates = $this->futureDates();
        $room = $this->makeRoom($fixtures['roomType'], '101');

        $this->createBookingPage($fixtures['admin'])
            ->fillForm($this->formData($fixtures['guest'], $room, $dates['checkIn'], $dates['checkOut']))
            ->set('data.total_price', 1)
            ->call('create')
            ->assertHasNoFormErrors();

        self::assertSame(300.0, (float) Booking::query()
            ->where('room_id', $room->id)
            ->whereDate('check_in', $dates['checkIn'])
            ->sole()
            ->total_price);
    }

    public function test_edit_form_recalculates_a_tampered_total_from_the_selected_room_and_stay(): void
    {
        $fixtures = $this->makeFixtures();
        $dates = $this->futureDates();
        $room = $this->makeRoom($fixtures['roomType'], '101');
        $booking = $this->makeBooking($fixtures['guest'], $room, $dates['checkIn'], $dates['checkOut'], 'pending');

        $this->editBookingPage($fixtures['admin'], $booking)
            ->fillForm($this->formData($fixtures['guest'], $room, $dates['checkIn'], $dates['checkOut']))
            ->set('data.total_price', 1)
            ->call('save')
            ->assertHasNoFormErrors();

        self::assertSame(300.0, (float) $booking->fresh()->total_price);
    }

    public function test_checked_in_edit_validates_the_locked_room_when_livewire_state_spoofs_another_room(): void
    {
        $fixtures = $this->makeFixtures();
        $originalDates = $this->futureDates();
        $conflictingCheckIn = $originalDates['checkOut']->copy()->addDays(2);
        $conflictingCheckOut = $conflictingCheckIn->copy()->addDays(2);
        $lockedRoom = $this->makeRoom($fixtures['roomType'], '101');
        $spoofedRoom = $this->makeRoom($fixtures['roomType'], '102');
        $booking = $this->makeBooking($fixtures['guest'], $lockedRoom, $originalDates['checkIn'], $originalDates['checkOut'], 'checked_in');

        $this->makeBooking($fixtures['guest'], $lockedRoom, $conflictingCheckIn, $conflictingCheckOut, 'pending');

        $this->editBookingPage($fixtures['admin'], $booking)
            ->fillForm($this->formData($fixtures['guest'], $lockedRoom, $conflictingCheckIn, $conflictingCheckOut))
            ->set('data.room_id', $spoofedRoom->id)
            ->call('save')
            ->assertHasFormErrors(['room_id']);

        $booking->refresh();

        self::assertSame($lockedRoom->id, $booking->room_id);
        self::assertSame($originalDates['checkIn']->toDateString(), $booking->check_in->toDateString());
        self::assertSame($originalDates['checkOut']->toDateString(), $booking->check_out->toDateString());
    }

    public function test_checked_in_edit_calculates_the_total_from_the_locked_room_when_livewire_state_spoofs_another_room(): void
    {
        $fixtures = $this->makeFixtures();
        $dates = $this->futureDates();
        $lockedRoom = $this->makeRoom($fixtures['roomType'], '101');
        $spoofedRoomType = RoomType::query()->create([
            'name' => 'Spoofed Room Type',
            'price_per_night' => 999,
            'capacity' => 2,
            'description' => 'Room type used to verify locked-room pricing.',
        ]);
        $spoofedRoom = $this->makeRoom($spoofedRoomType, '102');
        $booking = $this->makeBooking($fixtures['guest'], $lockedRoom, $dates['checkIn'], $dates['checkOut'], 'checked_in');

        $this->editBookingPage($fixtures['admin'], $booking)
            ->fillForm($this->formData($fixtures['guest'], $lockedRoom, $dates['checkIn'], $dates['checkOut']))
            ->set('data.room_id', $spoofedRoom->id)
            ->call('save')
            ->assertHasNoFormErrors();

        $booking->refresh();

        self::assertSame($lockedRoom->id, $booking->room_id);
        self::assertSame(300.0, (float) $booking->total_price);
    }

    public function test_create_form_rejects_a_malformed_date_without_creating_a_booking(): void
    {
        $fixtures = $this->makeFixtures();
        $dates = $this->futureDates();
        $room = $this->makeRoom($fixtures['roomType'], '101');

        $this->createBookingPage($fixtures['admin'])
            ->fillForm($this->formData($fixtures['guest'], $room, $dates['checkIn'], $dates['checkOut']))
            ->set('data.check_in', 'not-a-date')
            ->call('create')
            ->assertHasFormErrors(['check_in']);

        self::assertDatabaseCount('bookings', 0);
    }

    public function test_pending_edit_hydrates_persisted_dates_and_saves_without_refilling_them(): void
    {
        $fixtures = $this->makeFixtures();
        $dates = $this->futureDates();
        $room = $this->makeRoom($fixtures['roomType'], '101');
        $booking = $this->makeBooking($fixtures['guest'], $room, $dates['checkIn'], $dates['checkOut'], 'pending');

        $this->editBookingPage($fixtures['admin'], $booking)
            ->assertFormSet([
                'check_in' => $dates['checkIn']->toDateString(),
                'check_out' => $dates['checkOut']->toDateString(),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $booking->refresh();

        self::assertSame($dates['checkIn']->toDateString(), $booking->check_in->toDateString());
        self::assertSame($dates['checkOut']->toDateString(), $booking->check_out->toDateString());
        self::assertSame($room->id, $booking->room_id);
        self::assertSame(300.0, (float) $booking->total_price);
    }

    public function test_checked_in_edit_hydrates_persisted_dates_and_saves_without_refilling_them(): void
    {
        $fixtures = $this->makeFixtures();
        $dates = $this->futureDates();
        $room = $this->makeRoom($fixtures['roomType'], '101');
        $booking = $this->makeBooking($fixtures['guest'], $room, $dates['checkIn'], $dates['checkOut'], 'checked_in');

        $this->editBookingPage($fixtures['admin'], $booking)
            ->assertFormSet([
                'check_in' => $dates['checkIn']->toDateString(),
                'check_out' => $dates['checkOut']->toDateString(),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $booking->refresh();

        self::assertSame($dates['checkIn']->toDateString(), $booking->check_in->toDateString());
        self::assertSame($dates['checkOut']->toDateString(), $booking->check_out->toDateString());
        self::assertSame($room->id, $booking->room_id);
        self::assertSame(300.0, (float) $booking->total_price);
    }

    private function createBookingPage(User $admin): Testable
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return Livewire::actingAs($admin)->test(CreateBooking::class);
    }

    private function editBookingPage(User $admin, Booking $booking): Testable
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return Livewire::actingAs($admin)->test(EditBooking::class, ['record' => $booking->getRouteKey()]);
    }

    private function makeFixtures(): array
    {
        return [
            'admin' => User::factory()->create(['department' => 'admin']),
            'guest' => User::factory()->create(['department' => 'guest'])->guest,
            'roomType' => RoomType::query()->create([
                'name' => 'Availability Test Room',
                'price_per_night' => 150,
                'capacity' => 2,
                'description' => 'Room type for availability tests.',
            ]),
        ];
    }

    private function makeRoom(RoomType $roomType, string $roomNumber, string $status = 'available'): Room
    {
        return Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => $roomNumber,
            'status' => $status,
        ]);
    }

    private function makeBooking($guest, Room $room, Carbon $checkIn, Carbon $checkOut, string $status, string $holdStatus = 'pending'): Booking
    {
        return Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => $status,
            'hold_status' => $holdStatus,
            'total_price' => 0,
        ]);
    }

    private function futureDates(): array
    {
        $checkIn = Carbon::today()->addDays(10);

        return [
            'checkIn' => $checkIn,
            'checkOut' => $checkIn->copy()->addDays(2),
        ];
    }

    private function formData($guest, Room $room, Carbon $checkIn, Carbon $checkOut): array
    {
        return [
            'guest_id' => $guest->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'room_id' => $room->id,
            'status' => 'pending',
            'total_price' => 0,
        ];
    }
}
