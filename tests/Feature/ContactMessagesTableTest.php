<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\ContactMessages\Tables\ContactMessagesTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Tests\TestCase;

class ContactMessagesTableTest extends TestCase
{
    public function test_contact_messages_table_exposes_message_fields(): void
    {
        $table = ContactMessagesTable::configure(Table::make($this->createMock(HasTable::class)));

        self::assertNotNull($table->getColumn('name'));
        self::assertNotNull($table->getColumn('email'));
        self::assertNotNull($table->getColumn('subject'));
        self::assertNotNull($table->getColumn('message'));
        self::assertNotNull($table->getColumn('status'));
        self::assertNotNull($table->getColumn('created_at'));
    }
}
