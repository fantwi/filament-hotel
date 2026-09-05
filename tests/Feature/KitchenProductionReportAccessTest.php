<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\KitchenProductionReport;
use App\Filament\Admin\Widgets\KitchenProductionReportStats;
use App\Filament\Admin\Widgets\KitchenProductionStats;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KitchenProductionReportAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<class-string> */
    private const REPORT_WIDGETS = [
        KitchenProductionReportStats::class,
        KitchenProductionStats::class,
    ];

    #[DataProvider('authorizedRoles')]
    public function test_authorized_roles_can_open_the_complete_kitchen_production_report(string $roleName): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->syncRoles([Role::findByName($roleName, 'web')]);
        $this->actingAs($user);

        $this->get(KitchenProductionReport::getUrl())
            ->assertOk()
            ->assertSee('Kitchen Production vs Sales')
            ->assertSee('Kitchen production overview');

        self::assertTrue(KitchenProductionReport::canAccess());

        foreach (self::REPORT_WIDGETS as $widget) {
            self::assertTrue($widget::canView(), $widget.' should use the report access policy.');
        }
    }

    #[DataProvider('unauthorizedStaffRoles')]
    public function test_staff_without_report_permission_cannot_open_the_report(string $roleName): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->syncRoles([Role::findOrCreate($roleName, 'web')]);
        $this->actingAs($user);

        $this->get(KitchenProductionReport::getUrl())
            ->assertForbidden();

        self::assertFalse(KitchenProductionReport::canAccess());

        foreach (self::REPORT_WIDGETS as $widget) {
            self::assertFalse($widget::canView(), $widget.' should use the report access policy.');
        }
    }

    public function test_explicit_report_permission_grants_the_page_and_every_widget_together(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create(['department' => 'reception']);
        $user->givePermissionTo(Permission::findByName('view kitchen production reports', 'web'));
        $this->actingAs($user);

        $this->get(KitchenProductionReport::getUrl())
            ->assertOk()
            ->assertSee('Kitchen Production vs Sales');

        self::assertTrue(KitchenProductionReport::canAccess());

        foreach (self::REPORT_WIDGETS as $widget) {
            self::assertTrue($widget::canView(), $widget.' should honor an explicit report permission.');
        }
    }

    public function test_anonymous_users_are_redirected_to_the_admin_login(): void
    {
        $this->get(KitchenProductionReport::getUrl())
            ->assertRedirect('/admin/login');

        self::assertFalse(KitchenProductionReport::canAccess());

        foreach (self::REPORT_WIDGETS as $widget) {
            self::assertFalse($widget::canView(), $widget.' should not be visible anonymously.');
        }
    }

    public function test_canonical_role_seeder_preserves_kitchen_report_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $permission = Permission::findByName('view kitchen production reports', 'web');

        foreach (['super_admin', 'admin', 'manager', 'accountant', 'kitchen_manager', 'kitchen_staff'] as $roleName) {
            self::assertTrue(
                Role::findByName($roleName, 'web')->hasPermissionTo($permission),
                $roleName.' should retain kitchen production report access after permissions are synchronized.',
            );
        }

        self::assertFalse(
            Role::findByName('receptionist', 'web')->hasPermissionTo($permission),
            'Receptionists should not receive kitchen production report access from the canonical seeder.',
        );
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
            'kitchen manager' => ['kitchen_manager'],
            'kitchen staff' => ['kitchen_staff'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unauthorizedStaffRoles(): array
    {
        return [
            'receptionist' => ['receptionist'],
            'staff role without an assignment' => ['housekeeping'],
        ];
    }
}
