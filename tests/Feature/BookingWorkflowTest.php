<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_booking_can_be_rescheduled_when_dates_do_not_overlap(): void
    {
        $user = $this->makeAdminUser();

        [$room, $guest] = $this->makeBookingFixtures();

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-05-10',
            'check_out' => '2026-05-12',
            'status' => 'pending',
            'total_price' => 0,
        ]);

        $response = $this->actingAs($user)->postJson("/admin/bookings/{$booking->id}/reschedule", [
            'check_in' => '2026-05-12',
            'check_out' => '2026-05-14',
        ]);

        $response
            ->assertOk()
            ->assertJson(['success' => true]);

        $booking->refresh();

        $this->assertSame('2026-05-12', $booking->check_in->toDateString());
        $this->assertSame('2026-05-14', $booking->check_out->toDateString());
    }

    public function test_pending_booking_reschedule_is_rejected_when_dates_overlap(): void
    {
        $user = $this->makeAdminUser();

        [$room, $guest] = $this->makeBookingFixtures();

        Booking::create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-05-11',
            'check_out' => '2026-05-13',
            'status' => 'pending',
            'total_price' => 0,
        ]);

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-05-14',
            'check_out' => '2026-05-16',
            'status' => 'pending',
            'total_price' => 0,
        ]);

        $response = $this->actingAs($user)->postJson("/admin/bookings/{$booking->id}/reschedule", [
            'check_in' => '2026-05-12',
            'check_out' => '2026-05-15',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Room is already booked for those dates.',
            ]);
    }

    public function test_timeline_update_rejects_checked_in_bookings(): void
    {
        $user = $this->makeAdminUser();

        [$room, $guest] = $this->makeBookingFixtures();

        $booking = Booking::create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-05-10',
            'check_out' => '2026-05-12',
            'status' => 'checked_in',
            'total_price' => 0,
        ]);

        $response = $this->actingAs($user)->postJson("/admin/bookings/{$booking->id}/timeline-update", [
            'room_id' => $room->id,
            'check_in' => '2026-05-11',
            'check_out' => '2026-05-13',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Only pending bookings can move.',
            ]);
    }

    private function makeAdminUser(): User
    {
        $user = User::factory()->create([
            'department' => 'admin',
            'status' => 'online',
        ]);

        return $user;
    }

    private function makeBookingFixtures(): array
    {
        $roomType = RoomType::create([
            'name' => 'Standard',
            'price_per_night' => 150,
            'capacity' => 2,
            'description' => 'Standard room',
        ]);

        $room = Room::create([
            'room_type_id' => $roomType->id,
            'room_number' => '101',
            'status' => 'available',
        ]);

        $guest = User::factory()->create([
            'department' => 'guest',
        ])->guest;

        return [$room, $guest];
    }
}
