<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\RevenueReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RevenueReportAccessTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('authorizedRevenueRoles')]
    public function test_finance_authorized_roles_can_open_the_revenue_report(string $roleName): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate($roleName, 'web'));

        $this->actingAs($user)
            ->get(RevenueReport::getUrl())
            ->assertOk()
            ->assertSee('Revenue performance')
            ->assertSee('Export CSV')
            ->assertSee('Print report')
            ->assertSee('revenue-report-period-controls', false);
    }

    #[DataProvider('unauthorizedRevenueRoles')]
    public function test_roles_without_finance_access_cannot_open_the_revenue_report(string $roleName): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate($roleName, 'web'));

        $this->actingAs($user)
            ->get(RevenueReport::getUrl())
            ->assertForbidden();
    }

    public function test_authenticated_user_without_a_staff_role_cannot_open_the_revenue_report(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(RevenueReport::getUrl())
            ->assertForbidden();
    }

    public function test_anonymous_users_are_redirected_to_the_admin_login(): void
    {
        $this->get(RevenueReport::getUrl())
            ->assertRedirect('/admin/login');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function authorizedRevenueRoles(): array
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
    public static function unauthorizedRevenueRoles(): array
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
