<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\ActivityLogs\Tables\ActivityLogsTable;
use App\Filament\Admin\Resources\Bookings\Tables\BookingsTable;
use App\Filament\Admin\Resources\ConferenceFacilities\Tables\ConferenceFacilitiesTable;
use App\Filament\Admin\Resources\ConferenceRooms\Tables\ConferenceRoomsTable;
use App\Filament\Admin\Resources\ContactMessages\Tables\ContactMessagesTable;
use App\Filament\Admin\Resources\Facilities\Tables\FacilitiesTable;
use App\Filament\Admin\Resources\Guests\Tables\GuestsTable;
use App\Filament\Admin\Resources\Ingredients\Tables\IngredientsTable;
use App\Filament\Admin\Resources\KitchenStockMovements\Tables\KitchenStockMovementsTable;
use App\Filament\Admin\Resources\MenuCategories\Tables\MenuCategoriesTable;
use App\Filament\Admin\Resources\MenuItems\Tables\MenuItemsTable;
use App\Filament\Admin\Resources\Payments\Tables\PaymentsTable;
use App\Filament\Admin\Resources\RestaurantOrderItems\Tables\RestaurantOrderItemsTable;
use App\Filament\Admin\Resources\RestaurantOrders\Tables\RestaurantOrdersTable;
use App\Filament\Admin\Resources\RestaurantReservations\Tables\RestaurantReservationsTable;
use App\Filament\Admin\Resources\Restaurants\Tables\RestaurantsTable;
use App\Filament\Admin\Resources\RestaurantTables\Tables\RestaurantTablesTable;
use App\Filament\Admin\Resources\Rooms\Tables\RoomsTable;
use App\Filament\Admin\Resources\RoomTypes\Tables\RoomTypesTable;
use App\Filament\Admin\Resources\Users\Tables\UsersTable;
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
        array $actionNames,
    ): void {
        $table = $tableClass::configure(Table::make($this->createMock(HasTable::class)));

        self::assertSame($heading, $table->getEmptyStateHeading());
        self::assertNotEmpty($table->getEmptyStateDescription());
        self::assertSame($icon, $table->getEmptyStateIcon());

        $actions = $table->getEmptyStateActions();

        self::assertSame($actionNames, array_map(
            fn (Action $action): string => $action->getName(),
            $actions,
        ));
    }

    public static function operationalTables(): array
    {
        return [
            'hotel bookings' => [BookingsTable::class, 'No hotel bookings found', 'heroicon-o-calendar-days', ['resetFilters']],
            'food orders' => [RestaurantOrdersTable::class, 'No food orders found', 'heroicon-o-shopping-bag', ['resetFilters']],
            'table reservations' => [RestaurantReservationsTable::class, 'No table reservations found', 'heroicon-o-calendar-date-range', ['resetFilters']],
            'contact messages' => [ContactMessagesTable::class, 'No contact messages found', 'heroicon-o-envelope', ['resetFilters']],
            'guests' => [GuestsTable::class, 'No guests found', 'heroicon-o-users', ['resetFilters']],
            'payments' => [PaymentsTable::class, 'No payments found', 'heroicon-o-credit-card', ['resetFilters']],
            'conference rooms' => [ConferenceRoomsTable::class, 'No conference rooms found', 'heroicon-o-presentation-chart-bar', ['resetFilters']],
            'activity logs' => [ActivityLogsTable::class, 'No activity logs found', 'heroicon-o-clipboard-document-list', ['resetFilters']],
            'conference facilities' => [ConferenceFacilitiesTable::class, 'No conference facilities found', 'heroicon-o-building-office-2', ['create']],
            'facilities' => [FacilitiesTable::class, 'No facilities found', 'heroicon-o-building-office', ['create']],
            'ingredients' => [IngredientsTable::class, 'No ingredients found', 'heroicon-o-beaker', ['create', 'resetFilters']],
            'kitchen stock movements' => [KitchenStockMovementsTable::class, 'No kitchen stock movements found', 'heroicon-o-arrows-right-left', ['resetFilters']],
            'menu categories' => [MenuCategoriesTable::class, 'No menu categories found', 'heroicon-o-tag', ['create', 'resetFilters']],
            'menu items' => [MenuItemsTable::class, 'No menu items found', 'heroicon-o-clipboard-document-list', ['create', 'resetFilters']],
            'restaurant order items' => [RestaurantOrderItemsTable::class, 'No restaurant order items found', 'heroicon-o-queue-list', ['resetFilters']],
            'restaurant tables' => [RestaurantTablesTable::class, 'No restaurant tables found', 'heroicon-o-table-cells', ['create', 'resetFilters']],
            'restaurants' => [RestaurantsTable::class, 'No restaurants found', 'heroicon-o-building-storefront', ['create', 'resetFilters']],
            'room types' => [RoomTypesTable::class, 'No room types found', 'heroicon-o-home-modern', ['create']],
            'rooms' => [RoomsTable::class, 'No rooms found', 'heroicon-o-home', ['create']],
            'staff users' => [UsersTable::class, 'No staff users found', 'heroicon-o-user-group', ['create', 'resetFilters']],
        ];
    }
}
