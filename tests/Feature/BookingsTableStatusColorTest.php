<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Bookings\Tables\BookingsTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Tests\TestCase;

class BookingsTableStatusColorTest extends TestCase
{
    public function test_booking_status_column_handles_every_persisted_status(): void
    {
        $table = BookingsTable::configure(Table::make($this->createMock(HasTable::class)));
        $column = $table->getColumn('status');

        self::assertNotNull($column);
        self::assertSame('warning', $column->getColor('pending'));
        self::assertSame('info', $column->getColor('confirmed'));
        self::assertSame('success', $column->getColor('checked_in'));
        self::assertSame('gray', $column->getColor('checked_out'));
        self::assertSame('danger', $column->getColor('cancelled'));
        self::assertSame('danger', $column->getColor('expired'));
        self::assertSame('danger', $column->getColor('no_show'));
        self::assertSame('gray', $column->getColor('unexpected'));
    }

    public function test_booking_payment_status_column_handles_every_persisted_status(): void
    {
        $table = BookingsTable::configure(Table::make($this->createMock(HasTable::class)));
        $column = $table->getColumn('payment_status');

        self::assertNotNull($column);
        self::assertSame('success', $column->getColor('paid'));
        self::assertSame('warning', $column->getColor('pending'));
        self::assertSame('warning', $column->getColor('partial'));
        self::assertSame('warning', $column->getColor('partially_paid'));
        self::assertSame('danger', $column->getColor('unpaid'));
        self::assertSame('danger', $column->getColor('failed'));
        self::assertSame('danger', $column->getColor('expired'));
        self::assertSame('danger', $column->getColor('cancelled'));
        self::assertSame('gray', $column->getColor('refunded'));
        self::assertSame('gray', $column->getColor('unexpected'));
    }
}
