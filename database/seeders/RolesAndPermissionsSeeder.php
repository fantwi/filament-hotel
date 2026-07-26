<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view super admin dashboard', 'view admin dashboard', 'view accountant dashboard', 'view manager dashboard', 'view reception dashboard',
            'view users', 'create users', 'update users', 'delete users', 'manage roles and permissions',
            'view bookings', 'create bookings', 'update bookings', 'cancel bookings', 'check in guests', 'check out guests',
            'view conference bookings', 'manage conference bookings',
            'view restaurant reservations', 'manage restaurant reservations', 'view restaurant orders', 'manage restaurant orders', 'manage restaurant menu',
            'view payments', 'manage payments', 'view financial reports', 'process refunds', 'view operational reports', 'view activity logs',
            'manage kitchen orders', 'view kitchen dashboard', 'manage kitchen production', 'view kitchen production reports',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roles = collect(['super_admin', 'admin', 'accountant', 'manager', 'receptionist', 'kitchen_staff'])
            ->mapWithKeys(fn (string $name) => [$name => Role::findOrCreate($name, 'web')]);

        $roles['super_admin']->syncPermissions(Permission::all());
        $roles['admin']->syncPermissions([
            'view admin dashboard', 'view users', 'create users', 'update users',
            'view bookings', 'create bookings', 'update bookings', 'cancel bookings', 'check in guests', 'check out guests',
            'view conference bookings', 'manage conference bookings', 'view restaurant reservations', 'manage restaurant reservations',
            'view restaurant orders', 'manage restaurant orders', 'manage restaurant menu', 'view payments',
            'view operational reports', 'view activity logs', 'manage kitchen orders', 'view kitchen dashboard', 'manage kitchen production', 'view kitchen production reports',
        ]);
        $roles['accountant']->syncPermissions([
            'view accountant dashboard', 'view payments', 'manage payments', 'view financial reports', 'process refunds',
            'view bookings', 'view conference bookings', 'view restaurant reservations', 'view restaurant orders', 'view kitchen production reports',
        ]);
        $roles['manager']->syncPermissions([
            'view manager dashboard', 'view bookings', 'update bookings', 'view conference bookings', 'manage conference bookings',
            'view restaurant reservations', 'manage restaurant reservations', 'view restaurant orders', 'manage restaurant orders',
            'manage restaurant menu', 'view payments', 'view operational reports', 'view activity logs',
            'manage kitchen orders', 'view kitchen dashboard', 'manage kitchen production', 'view kitchen production reports',
        ]);
        $roles['receptionist']->syncPermissions([
            'view reception dashboard', 'view bookings', 'create bookings', 'update bookings', 'cancel bookings',
            'check in guests', 'check out guests', 'view conference bookings', 'manage conference bookings',
            'view restaurant reservations', 'manage restaurant reservations',
        ]);
        $roles['kitchen_staff']->syncPermissions([
            'manage kitchen orders', 'view kitchen dashboard', 'manage kitchen production', 'view kitchen production reports',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
