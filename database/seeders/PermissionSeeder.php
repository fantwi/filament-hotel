<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::findOrCreate('view restaurant reports', 'web');

        foreach (['super_admin', 'admin', 'manager', 'accountant'] as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->givePermissionTo($permission);
        }

        $manageKitchenOrders = Permission::findOrCreate('manage kitchen orders', 'web');
        $viewKitchenDashboard = Permission::findOrCreate('view kitchen dashboard', 'web');

        foreach (['super_admin', 'admin', 'manager', 'kitchen_staff'] as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->givePermissionTo([$manageKitchenOrders, $viewKitchenDashboard]);
        }
    }
}
