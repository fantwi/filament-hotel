<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\RestaurantOrderReport;
use App\Filament\Admin\Widgets\RestaurantOrderComparisonStats;
use App\Filament\Admin\Widgets\RestaurantOrderReportStats;
use App\Filament\Admin\Widgets\RestaurantOrderRevenueTrendChart;
use App\Filament\Admin\Widgets\RestaurantOrderVolumeTrendChart;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RestaurantOrderReportAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<class-string> */
    private const REPORT_WIDGETS = [
        RestaurantOrderReportStats::class,
        RestaurantOrderComparisonStats::class,
        RestaurantOrderVolumeTrendChart::class,
        RestaurantOrderRevenueTrendChart::class,
    ];

    #[DataProvider('authorizedRoles')]
    public function test_authorized_roles_can_open_the_complete_restaurant_report(string $roleName): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate($roleName, 'web'));

        $this->actingAs($user)
            ->get(RestaurantOrderReport::getUrl())
            ->assertOk()
            ->assertSee('Order performance')
            ->assertSee('Export CSV')
            ->assertSee('Print report')
            ->assertSee('restaurant-report-period-controls', false);

        foreach (self::REPORT_WIDGETS as $widget) {
            self::assertTrue($widget::canView(), $widget.' should use the report access policy.');
        }
    }

    #[DataProvider('unauthorizedRoles')]
    public function test_roles_without_restaurant_report_access_are_forbidden(string $roleName): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate($roleName, 'web'));
        $this->actingAs($user);

        $this->get(RestaurantOrderReport::getUrl())
            ->assertForbidden();

        foreach (self::REPORT_WIDGETS as $widget) {
            self::assertFalse($widget::canView(), $widget.' should use the report access policy.');
        }
    }

    public function test_explicit_report_permission_grants_the_page_and_every_widget_together(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('receptionist', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('view restaurant reports', 'web'));
        $this->actingAs($user);

        $this->get(RestaurantOrderReport::getUrl())
            ->assertOk()
            ->assertSee('Order performance');

        foreach (self::REPORT_WIDGETS as $widget) {
            self::assertTrue($widget::canView(), $widget.' should honor an explicit report permission.');
        }
    }

    public function test_authenticated_user_without_a_staff_role_cannot_open_the_restaurant_report(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(RestaurantOrderReport::getUrl())
            ->assertForbidden();
    }

    public function test_anonymous_users_are_redirected_to_the_admin_login(): void
    {
        $this->get(RestaurantOrderReport::getUrl())
            ->assertRedirect('/admin/login');
    }

    public function test_canonical_role_seeder_preserves_restaurant_report_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $permission = Permission::findByName('view restaurant reports', 'web');

        foreach (['super_admin', 'admin', 'manager', 'accountant'] as $roleName) {
            self::assertTrue(
                Role::findByName($roleName, 'web')->hasPermissionTo($permission),
                $roleName.' should retain restaurant report access after permissions are synchronized.',
            );
        }

        foreach (['receptionist', 'kitchen_manager', 'kitchen_staff'] as $roleName) {
            self::assertFalse(
                Role::findByName($roleName, 'web')->hasPermissionTo($permission),
                $roleName.' should not receive restaurant report access from the canonical seeder.',
            );
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function authorizedRoles(): array
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
    public static function unauthorizedRoles(): array
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
