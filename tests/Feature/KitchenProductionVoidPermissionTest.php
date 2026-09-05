<?php

namespace Tests\Feature;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KitchenProductionVoidPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_approved_management_roles_receive_the_void_permission(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $permission = Permission::query()
            ->where('name', 'void kitchen production')
            ->where('guard_name', 'web')
            ->first();

        self::assertNotNull($permission);

        foreach (['super_admin', 'admin', 'manager', 'kitchen_manager'] as $roleName) {
            self::assertTrue(Role::findByName($roleName, 'web')->hasPermissionTo($permission), $roleName);
        }

        self::assertFalse(Role::findByName('kitchen_staff', 'web')->hasPermissionTo($permission));
    }
}
