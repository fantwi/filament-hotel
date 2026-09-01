<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Users\Tables\UsersTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Tests\TestCase;

class UsersTableConfigurationTest extends TestCase
{
    public function test_users_table_exposes_the_fields_it_can_filter_and_group(): void
    {
        $table = UsersTable::configure(Table::make($this->createMock(HasTable::class)));

        foreach (['department_label', 'status'] as $name) {
            self::assertNotNull($table->getColumn($name), "Missing [{$name}] Users column.");
            self::assertTrue($table->getColumn($name)->isToggleable(), "[{$name}] should be toggleable.");
        }

        foreach (['department', 'status'] as $name) {
            self::assertNotNull($table->getFilter($name), "Missing [{$name}] Users filter.");
        }

        self::assertSame('department', $table->getDefaultGroup()?->getId());
    }

    public function test_users_table_no_longer_exposes_work_shift_controls(): void
    {
        $table = UsersTable::configure(Table::make($this->createMock(HasTable::class)));

        self::assertNull($table->getColumn('shift'));
        self::assertNull($table->getFilter('shift'));
        self::assertFalse($table->getColumn('department_label')->isToggledHiddenByDefault());
        self::assertFalse($table->getColumn('status')->isToggledHiddenByDefault());
    }
}
