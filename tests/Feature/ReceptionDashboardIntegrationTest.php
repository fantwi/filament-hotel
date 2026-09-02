<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\ReceptionArrivals;
use App\Filament\Admin\Widgets\ReceptionDepartures;
use App\Filament\Admin\Widgets\ReceptionDeskStats;
use App\Filament\Admin\Widgets\ReceptionStats;
use App\Filament\Admin\Widgets\RoleDashboardOverview;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReceptionDashboardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_authorized_receptionist_can_render_the_complete_dashboard_hierarchy(): void
    {
        $this->actingAs($this->receptionist());

        $this->get('/admin/reception-dashboard')
            ->assertOk()
            ->assertSeeInOrder([
                'Dashboard period',
                RoleDashboardOverview::class,
                ReceptionDeskStats::class,
                'Operations',
                ReceptionStats::class,
                ReceptionArrivals::class,
                ReceptionDepartures::class,
            ], escape: false);
    }

    public function test_staff_without_reception_dashboard_permission_cannot_open_it(): void
    {
        $role = Role::findOrCreate('receptionist', 'web');
        $user = User::factory()->create(['department' => 'reception']);
        $user->syncRoles([$role]);

        $this->actingAs($user)
            ->get('/admin/reception-dashboard')
            ->assertForbidden();
    }

    public function test_arrivals_queue_enforces_selected_dates_and_actionable_statuses(): void
    {
        $staff = $this->receptionist();
        [$guest, $room] = $this->hotelFixture('Arrival', 'Boundary');
        $start = $this->booking($guest, $room, 'pending', '2026-08-10', '2026-08-12');
        $end = $this->booking($guest, $room, 'confirmed', '2026-08-12', '2026-08-14');
        $checkedIn = $this->booking($guest, $room, 'checked_in', '2026-08-11', '2026-08-13');
        $cancelled = $this->booking($guest, $room, 'cancelled', '2026-08-11', '2026-08-13');
        $before = $this->booking($guest, $room, 'confirmed', '2026-08-09', '2026-08-11');
        $after = $this->booking($guest, $room, 'pending', '2026-08-13', '2026-08-15');

        $this->queueFor(ReceptionArrivals::class, $staff)
            ->assertCanSeeTableRecords([$start, $end])
            ->assertCanNotSeeTableRecords([$checkedIn, $cancelled, $before, $after]);
    }

    public function test_departures_queue_enforces_selected_dates_and_actionable_statuses(): void
    {
        $staff = $this->receptionist();
        [$guest, $room] = $this->hotelFixture('Departure', 'Boundary');
        $start = $this->booking($guest, $room, 'confirmed', '2026-08-08', '2026-08-10');
        $end = $this->booking($guest, $room, 'checked_in', '2026-08-10', '2026-08-12');
        $pending = $this->booking($guest, $room, 'pending', '2026-08-09', '2026-08-11');
        $cancelled = $this->booking($guest, $room, 'cancelled', '2026-08-09', '2026-08-11');
        $before = $this->booking($guest, $room, 'checked_in', '2026-08-07', '2026-08-09');
        $after = $this->booking($guest, $room, 'confirmed', '2026-08-11', '2026-08-13');

        $this->queueFor(ReceptionDepartures::class, $staff)
            ->assertCanSeeTableRecords([$start, $end])
            ->assertCanNotSeeTableRecords([$pending, $cancelled, $before, $after]);
    }

    public function test_live_reception_queues_search_guests_by_last_name(): void
    {
        $staff = $this->receptionist();
        [$targetGuest, $room] = $this->hotelFixture('Akosua', 'SearchableSurname');
        [$otherGuest] = $this->hotelFixture('SearchableSurname', 'UnrelatedSurname');
        $target = $this->booking($targetGuest, $room, 'confirmed', '2026-08-10', '2026-08-12');
        $other = $this->booking($otherGuest, $room, 'confirmed', '2026-08-10', '2026-08-12');

        foreach ([ReceptionArrivals::class, ReceptionDepartures::class] as $widgetClass) {
            $this->queueFor($widgetClass, $staff)
                ->searchTable('SearchableSurname')
                ->assertCanSeeTableRecords([$target, $other]);

            $this->queueFor($widgetClass, $staff)
                ->searchTable('UnrelatedSurname')
                ->assertCanSeeTableRecords([$other])
                ->assertCanNotSeeTableRecords([$target]);
        }
    }

    public function test_reception_queues_render_bookings_without_optional_times(): void
    {
        $staff = $this->receptionist();
        [$guest, $room] = $this->hotelFixture('No', 'ScheduleTime');
        $booking = $this->booking(
            $guest,
            $room,
            'confirmed',
            '2026-08-10',
            '2026-08-12',
            checkInTime: null,
            checkOutTime: null,
        );

        foreach ([ReceptionArrivals::class, ReceptionDepartures::class] as $widgetClass) {
            $this->queueFor($widgetClass, $staff)
                ->assertCanSeeTableRecords([$booking])
                ->assertSee('time not set')
                ->assertSee('Not set');
        }
    }

    /**
     * @param  class-string<ReceptionArrivals|ReceptionDepartures>  $widgetClass
     */
    private function queueFor(string $widgetClass, User $staff): Testable
    {
        return Livewire::actingAs($staff)->test($widgetClass, [
            'pageFilters' => [
                'period' => 'custom',
                'start_date' => '2026-08-10',
                'end_date' => '2026-08-12',
            ],
        ]);
    }

    private function receptionist(): User
    {
        $permission = Permission::findOrCreate('view reception dashboard', 'web');
        $role = Role::findOrCreate('receptionist', 'web');
        $role->givePermissionTo($permission);
        $user = User::factory()->create(['department' => 'reception']);
        $user->syncRoles([$role]);

        return $user;
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
            'name' => 'Reception Integration Room',
        ], [
            'price_per_night' => 100,
            'capacity' => 2,
        ]);
        $room = Room::query()->firstOrCreate([
            'room_number' => 'RECEPTION-INTEGRATION-101',
        ], [
            'room_type_id' => $roomType->id,
            'status' => 'available',
        ]);

        return [$guest, $room];
    }

    private function booking(
        Guest $guest,
        Room $room,
        string $status,
        string $checkIn,
        string $checkOut,
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
            'status' => $status,
            'payment_status' => 'partial',
        ]);
    }
}
