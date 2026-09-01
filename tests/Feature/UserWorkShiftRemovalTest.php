<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Users\Schemas\UserForm;
use App\Models\User;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Livewire\Component;
use Tests\TestCase;

class UserWorkShiftRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_schema_no_longer_stores_work_shifts(): void
    {
        self::assertFalse(DatabaseSchema::hasColumn('users', 'shift'));

        $user = User::factory()->create();

        self::assertArrayNotHasKey('shift', $user->getAttributes());
    }

    public function test_user_account_form_no_longer_accepts_a_work_shift(): void
    {
        $livewire = new class extends Component implements HasSchemas
        {
            use InteractsWithSchemas;
        };
        $form = UserForm::configure(Schema::make($livewire));

        self::assertNull($form->getComponent('shift'));
        self::assertNotNull($form->getComponent('department'));
        self::assertNotNull($form->getComponent('status'));
    }

    public function test_removal_migration_restores_the_previous_column_when_rolled_back(): void
    {
        $migration = require database_path('migrations/2026_09_01_000000_remove_shift_from_users_table.php');

        $migration->down();

        self::assertTrue(DatabaseSchema::hasColumn('users', 'shift'));

        $user = User::factory()->create();

        self::assertSame('off_duty', DB::table('users')->where('id', $user->id)->value('shift'));

        $migration->up();

        self::assertFalse(DatabaseSchema::hasColumn('users', 'shift'));
    }
}
