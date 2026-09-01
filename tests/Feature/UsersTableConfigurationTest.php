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

        foreach (['department_label', 'shift', 'status'] as $name) {
            self::assertNotNull($table->getColumn($name), "Missing [{$name}] Users column.");
            self::assertTrue($table->getColumn($name)->isToggleable(), "[{$name}] should be toggleable.");
        }

        foreach (['department', 'shift', 'status'] as $name) {
            self::assertNotNull($table->getFilter($name), "Missing [{$name}] Users filter.");
        }

        self::assertSame('department', $table->getDefaultGroup()?->getId());
    }

    public function test_shift_is_optional_but_department_and_status_remain_visible_by_default(): void
    {
        $table = UsersTable::configure(Table::make($this->createMock(HasTable::class)));

        self::assertTrue($table->getColumn('shift')->isToggledHiddenByDefault());
        self::assertFalse($table->getColumn('department_label')->isToggledHiddenByDefault());
        self::assertFalse($table->getColumn('status')->isToggledHiddenByDefault());
    }
}
