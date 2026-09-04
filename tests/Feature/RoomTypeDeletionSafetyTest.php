<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\RoomTypes\Pages\ListRoomTypes;
use App\Filament\Admin\Resources\RoomTypes\RoomTypeResource;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoomTypeDeletionSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_cannot_delete_a_room_type_that_is_assigned_to_a_room(): void
    {
        $admin = User::factory()->create(['department' => 'admin']);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $roomType = $this->createRoomType();

        Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => '101',
            'status' => 'available',
        ]);

        $this->actingAs($admin);

        self::assertFalse(RoomTypeResource::canDelete($roomType));
    }

    public function test_admin_can_delete_an_unused_room_type(): void
    {
        $admin = User::factory()->create(['department' => 'admin']);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        $this->actingAs($admin);

        self::assertTrue(RoomTypeResource::canDelete($this->createRoomType()));
    }

    public function test_database_rejects_room_type_deletion_without_removing_booking_history(): void
    {
        $roomType = $this->createRoomType();
        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => '102',
            'status' => 'available',
        ]);
        $guest = Guest::query()->create([
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'phone_number' => '0240000000',
            'email' => 'ama-room-history@example.test',
        ]);
        $booking = Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-12',
            'total_price' => 500,
            'status' => 'checked_out',
        ]);
        $payment = Payment::query()->create([
            'booking_id' => $booking->id,
            'guest_id' => $guest->id,
            'amount' => 500,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'ROOM-HISTORY-001',
        ]);

        try {
            $roomType->delete();
            self::fail('Deleting a room type with rooms should be rejected by the database.');
        } catch (QueryException) {
            // The restricted foreign key is the final safeguard for non-Filament deletes.
        }

        $this->assertDatabaseHas('room_types', ['id' => $roomType->id]);
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id]);
    }

    public function test_bulk_delete_skips_assigned_room_types_with_actionable_feedback(): void
    {
        $admin = User::factory()->create(['department' => 'admin']);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $assignedRoomType = $this->createRoomType();
        $unusedRoomType = $this->createRoomType(['name' => 'Unused room type']);

        Room::query()->create([
            'room_type_id' => $assignedRoomType->id,
            'room_number' => '103',
            'status' => 'available',
        ]);

        Livewire::actingAs($admin)
            ->test(ListRoomTypes::class)
            ->callTableBulkAction('delete', [$assignedRoomType, $unusedRoomType]);

        $notifications = session('filament.claimed_notifications', []);

        self::assertStringContainsString(
            'assigned to a room',
            (string) collect($notifications)->pluck('body')->implode(' '),
        );
        $this->assertDatabaseHas('room_types', ['id' => $assignedRoomType->id]);
        $this->assertDatabaseMissing('room_types', ['id' => $unusedRoomType->id]);
    }

    private function createRoomType(array $attributes = []): RoomType
    {
        return RoomType::query()->create(array_merge([
            'name' => 'History-safe room type',
            'price_per_night' => 250,
            'capacity' => 2,
            'description' => 'Used to verify that operational history is preserved.',
            'is_published' => true,
        ], $attributes));
    }
}
