<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\SuperAdminFinanceStats;
use App\Filament\Admin\Widgets\SuperAdminOperationsStats;
use App\Models\Booking;
use App\Models\CorporateOrganization;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class SuperAdminDashboardPeriodConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_rooms_used_counts_distinct_valid_stays_overlapping_the_selected_period(): void
    {
        [$guest, $room] = $this->hotelFixture();

        $this->createBooking($guest, $room, '2026-08-04', '2026-08-06', 'confirmed', '2026-07-15 10:00:00');
        $this->createBooking($guest, $room, '2026-08-20', '2026-08-22', 'confirmed', '2026-07-16 10:00:00');

        $outsideRoom = $this->room('PERIOD-2');
        $this->createBooking($guest, $outsideRoom, '2026-09-02', '2026-09-04', 'confirmed', '2026-08-10 10:00:00');
        $outsideRoom->forceFill(['status' => 'occupied'])->saveQuietly();

        $cancelledRoom = $this->room('PERIOD-3');
        $this->createBooking($guest, $cancelledRoom, '2026-08-12', '2026-08-14', 'cancelled', '2026-08-01 10:00:00');

        $stats = $this->statsFor(new SuperAdminOperationsStats, '2026-08-01', '2026-08-31');

        self::assertArrayHasKey('Rooms Used', $stats);
        self::assertArrayNotHasKey('Occupied Rooms', $stats);
        self::assertSame('1', $stats['Rooms Used']->getValue());
        self::assertSame('Aug 1, 2026 - Aug 31, 2026', $stats['Rooms Used']->getDescription());
    }

    public function test_corporate_accounts_added_only_counts_credit_enabled_accounts_created_in_the_selected_period(): void
    {
        $this->createOrganization('In-range account', true, '2026-08-10 10:00:00');
        $this->createOrganization('Older account', true, '2026-07-31 10:00:00');
        $this->createOrganization('Disabled account', false, '2026-08-12 10:00:00');

        $stats = $this->statsFor(new SuperAdminFinanceStats, '2026-08-01', '2026-08-31');

        self::assertArrayHasKey('Corporate Accounts Added', $stats);
        self::assertArrayNotHasKey('Corporate Accounts', $stats);
        self::assertSame('1', $stats['Corporate Accounts Added']->getValue());
        self::assertSame('Aug 1, 2026 - Aug 31, 2026', $stats['Corporate Accounts Added']->getDescription());
    }

    /**
     * @return array<string, Stat>
     */
    private function statsFor(object $widget, string $startDate, string $endDate): array
    {
        $widget->pageFilters = [
            'period' => 'monthly',
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return collect($method->invoke($widget))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat])
            ->all();
    }

    /**
     * @return array{0: Guest, 1: Room}
     */
    private function hotelFixture(): array
    {
        $guest = Guest::query()->create([
            'first_name' => 'Period',
            'last_name' => 'Guest',
            'email' => 'period-consistency@example.test',
            'phone_number' => '0240000000',
        ]);

        return [$guest, $this->room('PERIOD-1')];
    }

    private function room(string $number): Room
    {
        $roomType = RoomType::query()->firstOrCreate(
            ['name' => 'Period Consistency Room'],
            ['price_per_night' => 100, 'capacity' => 2],
        );

        return Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => $number,
            'status' => 'available',
        ]);
    }

    private function createBooking(Guest $guest, Room $room, string $checkIn, string $checkOut, string $status, string $createdAt): Booking
    {
        $booking = Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'total_price' => 100,
            'status' => $status,
            'payment_status' => 'pending',
        ]);

        return $this->createdAt($booking, $createdAt);
    }

    private function createOrganization(string $name, bool $creditEnabled, string $createdAt): CorporateOrganization
    {
        $organization = CorporateOrganization::query()->create([
            'name' => $name,
            'is_credit_enabled' => $creditEnabled,
        ]);

        return $this->createdAt($organization, $createdAt);
    }

    private function createdAt(Model $model, string $createdAt): Model
    {
        $model->forceFill([
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ])->saveQuietly();

        return $model;
    }
}
