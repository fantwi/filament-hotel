<?php

namespace Tests\Feature;

use App\Enums\StaffAccountStatus;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StaffAccountStatusDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_users_default_to_active_account_status(): void
    {
        $user = User::factory()->create();

        self::assertSame(StaffAccountStatus::Active, $user->status);
        self::assertTrue($user->hasActiveStaffAccount());
    }

    public function test_active_status_is_independent_of_online_presence(): void
    {
        Carbon::setTestNow('2026-09-02 12:00:00');

        $online = User::factory()->create([
            'status' => StaffAccountStatus::Active,
            'last_seen_at' => now()->subMinutes(4),
        ]);
        $offline = User::factory()->create([
            'status' => StaffAccountStatus::Active,
            'last_seen_at' => now()->subMinutes(6),
        ]);

        self::assertTrue($online->isOnline());
        self::assertFalse($offline->isOnline());
        self::assertSame(StaffAccountStatus::Active, $online->status);
        self::assertSame(StaffAccountStatus::Active, $offline->status);
    }

    public function test_restricted_staff_can_enter_the_panel_for_status_routing(): void
    {
        $panel = Filament::getPanel('admin');

        foreach ([StaffAccountStatus::OnLeave, StaffAccountStatus::Suspended] as $status) {
            $staff = User::factory()->create(['department' => 'admin', 'status' => $status]);
            $staff->syncRoles(['admin']);

            self::assertTrue($staff->canAccessPanel($panel));
        }
    }
}
