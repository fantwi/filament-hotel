<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'first_name' => 'Test',
                'last_name' => 'User',
                'department' => 'guest',
                'status' => User::STATUS_OFFLINE,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );

        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolesAndPermissionsSeeder::class,
            RestaurantSeeder::class,
            RestaurantFacilitySeeder::class,
            RestaurantTableSeeder::class,
            MenuCategorySeeder::class,
            MenuItemSeeder::class,
            DemoOperationalDataSeeder::class,
        ]);
    }
}
