<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Bookings\Tables\BookingsTable;
use App\Filament\Admin\Resources\ConferenceRooms\Tables\ConferenceRoomsTable;
use App\Filament\Admin\Resources\ContactMessages\Tables\ContactMessagesTable;
use App\Filament\Admin\Resources\Guests\Tables\GuestsTable;
use App\Filament\Admin\Resources\Payments\Tables\PaymentsTable;
use App\Filament\Admin\Resources\RestaurantOrders\Tables\RestaurantOrdersTable;
use App\Filament\Admin\Resources\RestaurantReservations\Tables\RestaurantReservationsTable;
use Filament\Actions\Action;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FilamentEmptyStatesTest extends TestCase
{
    /**
     * @param  class-string  $tableClass
     */
    #[DataProvider('operationalTables')]
    public function test_operational_tables_have_specific_recoverable_empty_states(
        string $tableClass,
        string $heading,
        string $icon,
    ): void {
        $table = $tableClass::configure(Table::make($this->createMock(HasTable::class)));

        self::assertSame($heading, $table->getEmptyStateHeading());
        self::assertNotEmpty($table->getEmptyStateDescription());
        self::assertSame($icon, $table->getEmptyStateIcon());

        $actions = $table->getEmptyStateActions();

        self::assertCount(1, $actions);
        self::assertInstanceOf(Action::class, $actions[0]);
        self::assertSame('resetFilters', $actions[0]->getName());
    }

    public static function operationalTables(): array
    {
        return [
            'hotel bookings' => [BookingsTable::class, 'No hotel bookings found', 'heroicon-o-calendar-days'],
            'food orders' => [RestaurantOrdersTable::class, 'No food orders found', 'heroicon-o-shopping-bag'],
            'table reservations' => [RestaurantReservationsTable::class, 'No table reservations found', 'heroicon-o-calendar-date-range'],
            'contact messages' => [ContactMessagesTable::class, 'No contact messages found', 'heroicon-o-envelope'],
            'guests' => [GuestsTable::class, 'No guests found', 'heroicon-o-users'],
            'payments' => [PaymentsTable::class, 'No payments found', 'heroicon-o-credit-card'],
            'conference rooms' => [ConferenceRoomsTable::class, 'No conference rooms found', 'heroicon-o-presentation-chart-bar'],
        ];
    }
}
