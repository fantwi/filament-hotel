<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSION = 'export kitchen stock movements';

    private const DEFAULT_ROLES = ['super_admin', 'admin', 'accountant', 'manager', 'kitchen_manager'];

    /**
     * Adds the export capability without resetting any customized role permissions.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::findOrCreate(self::PERMISSION, 'web');

        foreach (self::DEFAULT_ROLES as $roleName) {
            Role::findOrCreate($roleName, 'web')->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Removes only the permission introduced by this migration.
     */
    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()
            ->where('name', self::PERMISSION)
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
