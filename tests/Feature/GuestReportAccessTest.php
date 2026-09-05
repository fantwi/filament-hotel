<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\GuestReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuestReportAccessTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('authorizedGuestReportRoles')]
    public function test_authorized_roles_can_open_the_guest_report(string $roleName): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate($roleName, 'web'));

        $this->actingAs($user)
            ->get(GuestReport::getUrl())
            ->assertOk()
            ->assertSee('Guest activity report')
            ->assertSee('Export CSV')
            ->assertSee('Print report')
            ->assertSee('guest-report-period-controls', false);
    }

    #[DataProvider('unauthorizedGuestReportRoles')]
    public function test_roles_without_guest_report_access_are_forbidden(string $roleName): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate($roleName, 'web'));

        $this->actingAs($user)
            ->get(GuestReport::getUrl())
            ->assertForbidden();
    }

    public function test_authenticated_user_without_a_staff_role_cannot_open_the_guest_report(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(GuestReport::getUrl())
            ->assertForbidden();
    }

    public function test_anonymous_users_are_redirected_to_the_admin_login(): void
    {
        $this->get(GuestReport::getUrl())
            ->assertRedirect('/admin/login');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function authorizedGuestReportRoles(): array
    {
        return [
            'super admin' => ['super_admin'],
            'admin' => ['admin'],
            'manager' => ['manager'],
            'accountant' => ['accountant'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unauthorizedGuestReportRoles(): array
    {
        return [
            'receptionist' => ['receptionist'],
            'housekeeping' => ['housekeeping'],
            'kitchen manager' => ['kitchen_manager'],
            'kitchen staff' => ['kitchen_staff'],
            'guest' => ['guest'],
        ];
    }
}
