<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Bookings\Tables\BookingsTable;
use Filament\Actions\ActionGroup;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
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

    public function test_booking_table_exposes_operational_filters(): void
    {
        $table = BookingsTable::configure(Table::make($this->createMock(HasTable::class)));
        $filters = $table->getFilters();

        self::assertSame([
            'status',
            'payment_status',
            'room_type_id',
            'corporate_organization_id',
            'check_in',
            'check_out',
            'balance',
        ], array_keys($filters));
        self::assertInstanceOf(SelectFilter::class, $filters['status']);
        self::assertInstanceOf(SelectFilter::class, $filters['payment_status']);
        self::assertInstanceOf(SelectFilter::class, $filters['room_type_id']);
        self::assertInstanceOf(SelectFilter::class, $filters['corporate_organization_id']);
        self::assertInstanceOf(Filter::class, $filters['check_in']);
        self::assertInstanceOf(Filter::class, $filters['check_out']);
        self::assertInstanceOf(SelectFilter::class, $filters['balance']);
        self::assertSame(3, $table->getFiltersFormColumns());
    }

    public function test_check_in_and_check_out_actions_require_confirmation_and_feedback(): void
    {
        $table = BookingsTable::configure(Table::make($this->createMock(HasTable::class)));
        $group = collect($table->getRecordActions())->first(
            fn ($action): bool => $action instanceof ActionGroup,
        );

        self::assertInstanceOf(ActionGroup::class, $group);

        $actions = $group->getFlatActions();

        self::assertTrue($actions['check_in']->isConfirmationRequired());
        self::assertTrue($actions['check_out']->isConfirmationRequired());
        self::assertSame('Guest checked in', $actions['check_in']->getSuccessNotificationTitle());
        self::assertSame('Guest checked out', $actions['check_out']->getSuccessNotificationTitle());
    }
}
