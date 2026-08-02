<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $roles = [
            'super_admin',
            'admin',
            'manager',
            'receptionist',
            'housekeeping',
            'accountant',
            'kitchen_manager',
            'kitchen_staff',
        ];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }
}
