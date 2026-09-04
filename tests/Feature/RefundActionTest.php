<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Bookings\Pages\ListBookings;
use App\Filament\Admin\Resources\RestaurantReservations\Pages\ListRestaurantReservations;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\RestaurantReservation;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class RefundActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_hotel_refund_action_timestamps_payments_and_updates_the_booking(): void
    {
        [$admin, $guest] = $this->users();
        $booking = $this->hotelBooking($guest);
        $payment = $this->payment($guest, 'booking_id', $booking->id, 'HOTEL-REFUND-ACTION');

        Carbon::setTestNow('2026-08-20 12:00:00');

        try {
            Livewire::actingAs($admin)
                ->test(ListBookings::class)
                ->callTableAction('refund', $booking, ['reason' => 'Approved cancellation'])
                ->assertOk();
        } finally {
            Carbon::setTestNow();
        }

        self::assertSame('cancelled', $booking->fresh()->status);
        self::assertSame('refunded', $booking->fresh()->payment_status);
        self::assertSame('refunded', $payment->fresh()->payment_status);
        self::assertSame('2026-08-20 12:00:00', $payment->fresh()->refunded_at?->toDateTimeString());
    }

    public function test_table_reservation_refund_action_timestamps_the_linked_payment(): void
    {
        Mail::fake();

        [$admin, $guest] = $this->users();
        $reservation = $this->tableReservation($guest);
        $payment = $this->payment(
            $guest,
            'restaurant_reservation_id',
            $reservation->id,
            'TABLE-REFUND-ACTION',
        );

        Carbon::setTestNow('2026-08-21 13:00:00');

        try {
            Livewire::actingAs($admin)
                ->test(ListRestaurantReservations::class)
                ->callTableAction('refund', $reservation)
                ->assertOk();
        } finally {
            Carbon::setTestNow();
        }

        self::assertSame('refunded', $reservation->fresh()->payment_status);
        self::assertSame('refunded', $payment->fresh()->payment_status);
        self::assertSame('2026-08-21 13:00:00', $payment->fresh()->refunded_at?->toDateTimeString());
    }

    /**
     * @return array{0: User, 1: Guest}
     */
    private function users(): array
    {
        return [
            User::factory()->create(['department' => 'admin']),
            User::factory()->create([
                'department' => 'guest',
                'phone_number' => '0240000000',
            ])->guest,
        ];
    }

    private function hotelBooking(Guest $guest): Booking
    {
        $roomType = RoomType::query()->create([
            'name' => 'Refund Action Room Type',
            'price_per_night' => 100,
            'capacity' => 2,
        ]);
        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'REFUND-101',
            'status' => 'available',
        ]);

        return Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-11',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
    }

    private function tableReservation(Guest $guest): RestaurantReservation
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Refund Action Restaurant',
            'description' => 'Restaurant fixture for refund action tests.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $table = RestaurantTable::query()->create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'REFUND-T1',
            'capacity' => 4,
        ]);

        return RestaurantReservation::query()->create([
            'restaurant_id' => $restaurant->id,
            'restaurant_table_id' => $table->id,
            'guest_id' => $guest->id,
            'guest_name' => $guest->full_name,
            'guest_email' => $guest->email,
            'guest_phone' => $guest->phone_number,
            'reservation_date' => '2026-10-10',
            'reservation_time' => '18:00',
            'number_of_guests' => 2,
            'reservation_fee' => 100,
            'status' => 'confirmed',
            'payment_status' => 'completed',
        ]);
    }

    private function payment(
        Guest $guest,
        string $foreignKey,
        int $foreignId,
        string $reference,
    ): Payment {
        return Payment::query()->create([
            'guest_id' => $guest->id,
            $foreignKey => $foreignId,
            'amount' => 100,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => $reference,
        ]);
    }
}
