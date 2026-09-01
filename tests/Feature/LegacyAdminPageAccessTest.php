<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboards\RoleDashboard;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyAdminPageAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_dashboard_redirects_to_role_dashboard(): void
    {
        $admin = User::factory()->create(['department' => 'admin']);

        $this->actingAs($admin)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertRedirect(RoleDashboard::getUrl());
    }

    public function test_legacy_live_staff_page_redirects_to_users(): void
    {
        $admin = User::factory()->create(['department' => 'admin']);

        $this->actingAs($admin)
            ->get(route('filament.admin.pages.live-staff-dashboard'))
            ->assertRedirect(UserResource::getUrl('index'));
    }

    public function test_legacy_admin_pages_require_filament_authentication(): void
    {
        $this->get(route('filament.admin.pages.dashboard'))
            ->assertRedirect(route('filament.admin.auth.login'));

        $this->get(route('filament.admin.pages.live-staff-dashboard'))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_unauthorized_staff_cannot_use_legacy_live_staff_page_to_bypass_users_access(): void
    {
        $receptionist = User::factory()->create(['department' => 'reception']);

        $this->actingAs($receptionist)
            ->followingRedirects()
            ->get(route('filament.admin.pages.live-staff-dashboard'))
            ->assertForbidden();
    }
}
