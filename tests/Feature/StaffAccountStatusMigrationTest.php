<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffAccountStatusMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_normalizes_legacy_statuses_and_sets_active_default(): void
    {
        $migration = require database_path('migrations/2026_09_02_000100_normalize_staff_account_statuses.php');
        $migration->down();

        $legacyIds = [];

        foreach (['online', 'offline', 'on_leave', 'suspended', 'unexpected'] as $index => $status) {
            $legacyIds[$status] = DB::table('users')->insertGetId([
                'first_name' => 'Legacy',
                'last_name' => (string) $index,
                'email' => "legacy-{$index}@example.test",
                'password' => Hash::make('password'),
                'department' => 'admin',
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $migration->up();

        self::assertSame('active', DB::table('users')->find($legacyIds['online'])->status);
        self::assertSame('active', DB::table('users')->find($legacyIds['offline'])->status);
        self::assertSame('on_leave', DB::table('users')->find($legacyIds['on_leave'])->status);
        self::assertSame('suspended', DB::table('users')->find($legacyIds['suspended'])->status);
        self::assertSame('suspended', DB::table('users')->find($legacyIds['unexpected'])->status);

        $id = DB::table('users')->insertGetId([
            'first_name' => 'Default',
            'last_name' => 'Status',
            'email' => 'default-status@example.test',
            'password' => Hash::make('password'),
            'department' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        self::assertSame('active', DB::table('users')->find($id)->status);
    }
}
