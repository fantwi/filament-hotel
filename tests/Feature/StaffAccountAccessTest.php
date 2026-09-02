<?php

namespace Tests\Feature;

use App\Enums\StaffAccountStatus;
use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\StaffAccountAccess;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Component;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StaffAccountAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_on_leave_staff_are_limited_to_their_dashboard_profile_and_logout(): void
    {
        $manager = $this->staff('manager', StaffAccountStatus::OnLeave);
        $access = app(StaffAccountAccess::class);

        self::assertTrue($access->allows($manager, 'filament.admin.pages.role-dashboard'));
        self::assertTrue($access->allows($manager, 'filament.admin.pages.manager-dashboard'));
        self::assertTrue($access->allows($manager, 'filament.admin.auth.profile'));
        self::assertTrue($access->allows($manager, 'filament.admin.auth.logout'));
        self::assertFalse($access->allows($manager, 'filament.admin.pages.admin-dashboard'));
        self::assertFalse($access->allows($manager, 'filament.admin.resources.bookings.index'));
    }

    public function test_suspended_staff_are_limited_to_profile_and_logout(): void
    {
        $admin = $this->staff('admin', StaffAccountStatus::Suspended);
        $access = app(StaffAccountAccess::class);

        self::assertTrue($access->allows($admin, 'filament.admin.auth.profile'));
        self::assertTrue($access->allows($admin, 'filament.admin.auth.logout'));
        self::assertFalse($access->allows($admin, 'filament.admin.pages.role-dashboard'));
        self::assertFalse($access->allows($admin, 'filament.admin.pages.admin-dashboard'));
        self::assertFalse($access->allows($admin, 'filament.admin.resources.users.index'));
    }

    public function test_active_staff_pass_the_status_route_gate_for_downstream_authorization(): void
    {
        $receptionist = $this->staff('receptionist', StaffAccountStatus::Active);
        $access = app(StaffAccountAccess::class);

        self::assertTrue($access->allows($receptionist, 'filament.admin.pages.admin-dashboard'));
        self::assertTrue($access->allows($receptionist, 'filament.admin.resources.users.index'));
    }

    public function test_on_leave_staff_without_an_assigned_dashboard_do_not_pass_an_unnamed_route(): void
    {
        $housekeeper = $this->staff('housekeeping', StaffAccountStatus::OnLeave);

        self::assertFalse(app(StaffAccountAccess::class)->allows($housekeeper, null));
    }

    public function test_on_leave_staff_without_an_assigned_dashboard_are_redirected_to_profile(): void
    {
        $housekeeper = $this->staff('housekeeping', StaffAccountStatus::OnLeave);

        $this->actingAs($housekeeper)
            ->get('/admin')
            ->assertRedirect(route('filament.admin.auth.profile'))
            ->assertSessionHas('staff_account_notice', StaffAccountAccess::LEAVE_MESSAGE);
    }

    public function test_non_staff_users_pass_the_status_route_gate_for_downstream_authorization(): void
    {
        $guest = User::factory()->create([
            'department' => 'guest',
            'status' => StaffAccountStatus::Suspended,
        ]);

        self::assertTrue(app(StaffAccountAccess::class)->allows(
            $guest,
            'filament.admin.resources.bookings.index',
        ));
    }

    public function test_on_leave_staff_are_redirected_from_operational_pages_to_their_dashboard(): void
    {
        $manager = $this->staff('manager', StaffAccountStatus::OnLeave);

        $this->actingAs($manager)
            ->get('/admin/bookings')
            ->assertRedirect(route('filament.admin.pages.manager-dashboard'))
            ->assertSessionHas('staff_account_notice', StaffAccountAccess::LEAVE_MESSAGE);

        self::assertTrue($manager->fresh()->hasRole('manager'));
    }

    public function test_on_leave_staff_can_view_only_their_assigned_dashboard_with_the_notice(): void
    {
        $manager = $this->staff('manager', StaffAccountStatus::OnLeave);

        $this->actingAs($manager)
            ->get(route('filament.admin.pages.manager-dashboard'))
            ->assertOk()
            ->assertSeeText(StaffAccountAccess::LEAVE_MESSAGE);

        $this->get(route('filament.admin.pages.admin-dashboard'))
            ->assertRedirect(route('filament.admin.pages.manager-dashboard'))
            ->assertSessionHas('staff_account_notice', StaffAccountAccess::LEAVE_MESSAGE);
    }

    public function test_suspended_staff_are_redirected_to_profile_with_the_exact_notice(): void
    {
        $admin = $this->staff('admin', StaffAccountStatus::Suspended);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertRedirect(route('filament.admin.auth.profile'))
            ->assertSessionHas('staff_account_notice', StaffAccountAccess::SUSPENSION_MESSAGE);

        $this->get(route('filament.admin.auth.profile'))
            ->assertOk()
            ->assertSeeText(StaffAccountAccess::SUSPENSION_MESSAGE);
    }

    public function test_restricted_staff_cannot_forge_direct_admin_mutations(): void
    {
        $manager = $this->staff('manager', StaffAccountStatus::OnLeave);
        $booking = $this->booking();

        $this->actingAs($manager)
            ->postJson(route('admin.bookings.reschedule', $booking), [
                'check_in' => '2026-09-10',
                'check_out' => '2026-09-12',
            ])
            ->assertForbidden();

        self::assertSame('2026-09-03', $booking->fresh()->check_in?->toDateString());
        self::assertSame('2026-09-05', $booking->fresh()->check_out?->toDateString());
    }

    public function test_safe_direct_admin_requests_redirect_restricted_staff(): void
    {
        $manager = $this->staff('manager', StaffAccountStatus::OnLeave);

        $this->actingAs($manager)
            ->getJson(route('admin.calendar-events'))
            ->assertRedirect(route('filament.admin.pages.manager-dashboard'))
            ->assertSessionHas('staff_account_notice', StaffAccountAccess::LEAVE_MESSAGE);
    }

    public function test_secure_resources_reject_restricted_staff_before_downstream_capabilities(): void
    {
        $manager = $this->staff('manager', StaffAccountStatus::OnLeave);
        $this->actingAs($manager);
        self::assertFalse(BookingResource::canAccess());

        $admin = $this->staff('admin', StaffAccountStatus::Suspended);
        $this->actingAs($admin);
        self::assertFalse(UserResource::canAccess());
    }

    public function test_restricted_staff_navigation_is_hidden_while_active_navigation_remains_enabled(): void
    {
        $onLeave = $this->staff('manager', StaffAccountStatus::OnLeave);
        $suspended = $this->staff('admin', StaffAccountStatus::Suspended);
        $active = $this->staff('receptionist', StaffAccountStatus::Active);
        $panel = Filament::getPanel('admin');

        $this->actingAs($onLeave);
        self::assertFalse($panel->hasNavigation());

        $this->actingAs($suspended);
        self::assertFalse($panel->hasNavigation());

        $this->actingAs($active);
        self::assertTrue($panel->hasNavigation());
    }

    public function test_suspended_staff_can_still_view_the_login_page_and_log_out(): void
    {
        $this->get(route('filament.admin.auth.login'))->assertOk();

        $suspended = $this->staff('admin', StaffAccountStatus::Suspended);

        $this->actingAs($suspended)
            ->post(route('filament.admin.auth.logout'))
            ->assertRedirect(route('filament.admin.auth.login'));

        $this->assertGuest();
    }

    public function test_persistent_middleware_blocks_livewire_actions_after_staff_become_restricted(): void
    {
        $manager = $this->staff('manager', StaffAccountStatus::Active);
        $target = User::factory()->create([
            'department' => 'guest',
            'phone_number' => 'original',
        ]);

        Livewire::component('staff-account-mutation-probe', StaffAccountMutationProbe::class);
        Route::middleware(['web', 'auth'])
            ->get('/admin/staff-account-livewire-probe/{target}', fn (User $target): string => Blade::render(
                '<livewire:staff-account-mutation-probe :target-user-id="$targetUserId" />',
                ['targetUserId' => $target->getKey()],
            ));

        $response = $this->actingAs($manager)->get("/admin/staff-account-livewire-probe/{$target->getKey()}");
        $response->assertOk();

        $manager->update(['status' => StaffAccountStatus::OnLeave]);
        Filament::setCurrentPanel(null);

        $this->withHeader('X-Livewire', 'true')
            ->postJson(app('livewire')->getUpdateUri(), [
                'components' => [[
                    'snapshot' => $this->livewireSnapshot($response->getContent()),
                    'updates' => [],
                    'calls' => [[
                        'path' => '',
                        'method' => 'mutate',
                        'params' => [],
                    ]],
                ]],
            ])
            ->assertForbidden();

        self::assertSame('original', $target->fresh()->phone_number);
    }

    private function staff(string $role, StaffAccountStatus $status): User
    {
        $department = match ($role) {
            'manager' => 'management',
            'receptionist' => 'reception',
            default => $role,
        };

        $staff = User::factory()->create([
            'department' => $department,
            'status' => $status,
        ]);
        $staff->syncRoles([$role]);

        $dashboardPermission = match ($role) {
            'kitchen_manager' => 'view kitchen manager dashboard',
            'kitchen_staff' => 'view kitchen staff dashboard',
            'super_admin' => 'view super admin dashboard',
            'admin' => 'view admin dashboard',
            'accountant' => 'view accountant dashboard',
            'manager' => 'view manager dashboard',
            'receptionist' => 'view receptionist dashboard',
            default => null,
        };

        if ($dashboardPermission !== null) {
            $staff->roles()->firstOrFail()->givePermissionTo(
                Permission::findOrCreate($dashboardPermission, 'web'),
            );
        }

        return $staff;
    }

    private function booking(): Booking
    {
        $roomType = RoomType::create([
            'name' => 'Status Access Room',
            'price_per_night' => 150,
            'capacity' => 2,
            'description' => 'Room used to verify the staff account status boundary.',
        ]);
        $room = Room::create([
            'room_type_id' => $roomType->getKey(),
            'room_number' => 'STATUS-101',
            'status' => 'available',
        ]);
        $guest = User::factory()->create(['department' => 'guest'])->guest;

        return Booking::create([
            'guest_id' => $guest->getKey(),
            'room_id' => $room->getKey(),
            'check_in' => '2026-09-03',
            'check_out' => '2026-09-05',
            'status' => 'pending',
            'total_price' => 300,
        ]);
    }

    private function livewireSnapshot(string $html): string
    {
        $document = new \DOMDocument;
        @$document->loadHTML($html);

        foreach ($document->getElementsByTagName('*') as $element) {
            if (! $element->hasAttribute('wire:snapshot')) {
                continue;
            }

            $snapshot = htmlspecialchars_decode($element->getAttribute('wire:snapshot'), ENT_QUOTES | ENT_SUBSTITUTE);
            json_decode($snapshot, true, flags: JSON_THROW_ON_ERROR);

            return $snapshot;
        }

        self::fail('The Livewire probe response did not contain a component snapshot.');
    }
}

class StaffAccountMutationProbe extends Component
{
    public int $targetUserId;

    public function mutate(): void
    {
        User::query()->whereKey($this->targetUserId)->update([
            'phone_number' => 'mutated-by-livewire',
        ]);
    }

    public function render(): string
    {
        return '<div>Staff account mutation probe</div>';
    }
}
