<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds initial data for restore admin seeder.
 */
class RestoreAdminSeeder extends Seeder
{
    /**
     * Performs the run data-seeding operation.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@filament-hotel.test'],
            [
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'department' => 'super_admin',
                'status' => User::STATUS_ONLINE,
                'email_verified_at' => now(),
                'password' => Hash::make('Admin@2026!'),
            ],
        );

        $user->syncRoles(['super_admin']);
    }
}
