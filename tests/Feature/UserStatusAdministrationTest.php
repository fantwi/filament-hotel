<?php

namespace Tests\Feature;

use App\Enums\StaffAccountStatus;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Tables\UsersTable;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class UserStatusAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_status_form_and_filter_expose_only_account_states(): void
    {
        $admin = $this->admin();

        $component = Livewire::actingAs($admin)->test(CreateUser::class);
        $status = $component->instance()->form->getComponent('status');
        $table = UsersTable::configure(Table::make($this->createMock(HasTable::class)));

        self::assertSame([
            'active' => 'Active',
            'on_leave' => 'On Leave',
            'suspended' => 'Suspended',
        ], $status->getOptions());
        self::assertSame('active', $status->getDefaultState());
        self::assertSame([
            'active' => 'Active',
            'on_leave' => 'On Leave',
            'suspended' => 'Suspended',
        ], $table->getFilter('status')?->getOptions());
    }

    public function test_active_staff_status_renders_online_and_offline_presence_accessibly(): void
    {
        Carbon::setTestNow('2026-09-02 12:00:00');

        try {
            $online = User::factory()->create([
                'status' => StaffAccountStatus::Active,
                'last_seen_at' => now()->subMinutes(4),
            ]);
            $offline = User::factory()->create([
                'status' => StaffAccountStatus::Active,
                'last_seen_at' => now()->subMinutes(6),
            ]);

            $onlineHtml = view('filament.admin.components.staff-status', ['user' => $online])->render();
            $offlineHtml = view('filament.admin.components.staff-status', ['user' => $offline])->render();

            self::assertStringContainsString('Active', $onlineHtml);
            self::assertStringContainsString('Online', $onlineHtml);
            self::assertStringContainsString('bg-success-500', $onlineHtml);
            self::assertStringContainsString('animate-ping', $onlineHtml);
            self::assertStringContainsString('Active', $offlineHtml);
            self::assertStringContainsString('Offline', $offlineHtml);
            self::assertStringContainsString('bg-danger-500', $offlineHtml);
            self::assertStringContainsString('animate-ping', $offlineHtml);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_non_active_staff_statuses_render_badges_without_presence_text(): void
    {
        $onLeave = User::factory()->create(['status' => StaffAccountStatus::OnLeave]);
        $suspended = User::factory()->create(['status' => StaffAccountStatus::Suspended]);

        $onLeaveHtml = view('filament.admin.components.staff-status', ['user' => $onLeave])->render();
        $suspendedHtml = view('filament.admin.components.staff-status', ['user' => $suspended])->render();

        self::assertStringContainsString('On Leave', $onLeaveHtml);
        self::assertStringNotContainsString('Online', $onLeaveHtml);
        self::assertStringNotContainsString('Offline', $onLeaveHtml);
        self::assertStringContainsString('Suspended', $suspendedHtml);
        self::assertStringNotContainsString('Online', $suspendedHtml);
        self::assertStringNotContainsString('Offline', $suspendedHtml);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['department' => 'admin']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $admin;
    }
}
