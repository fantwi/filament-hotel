<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\ConferenceRooms\Pages\ListConferenceRooms;
use App\Filament\Admin\Resources\ConferenceRooms\Tables\ConferenceRoomsTable;
use App\Filament\Admin\Resources\Guests\Tables\GuestsTable;
use App\Filament\Admin\Resources\MenuCategories\Tables\MenuCategoriesTable;
use App\Filament\Admin\Resources\MenuItems\Tables\MenuItemsTable;
use App\Filament\Admin\Resources\RestaurantOrderItems\Tables\RestaurantOrderItemsTable;
use App\Filament\Admin\Resources\RestaurantReservations\Tables\RestaurantReservationsTable;
use App\Filament\Admin\Resources\Restaurants\Tables\RestaurantsTable;
use App\Filament\Admin\Resources\RestaurantTables\Pages\ListRestaurantTables;
use App\Filament\Admin\Resources\RestaurantTables\Tables\RestaurantTablesTable;
use App\Models\ConferenceRoom;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FilamentOperationalTableFiltersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  class-string  $tableClass
     * @param  list<string>  $filterNames
     */
    #[DataProvider('operationalFilters')]
    public function test_operational_tables_register_the_filters_staff_need(string $tableClass, array $filterNames): void
    {
        $table = $tableClass::configure(Table::make($this->createMock(HasTable::class)));

        foreach ($filterNames as $filterName) {
            self::assertNotNull(
                $table->getFilter($filterName),
                "Missing [{$filterName}] filter on [{$tableClass}].",
            );
        }
    }

    public function test_restaurant_table_status_filter_and_search_change_visible_records(): void
    {
        $admin = User::factory()->create(['department' => 'admin']);
        $restaurant = Restaurant::query()->create([
            'name' => 'Filter Test Restaurant',
            'description' => 'Restaurant used to verify Filament table controls.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
            'capacity' => 20,
            'is_open' => true,
            'is_published' => true,
        ]);
        $available = RestaurantTable::query()->create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'AVAILABLE-01',
            'capacity' => 2,
            'status' => 'available',
        ]);
        $maintenance = RestaurantTable::query()->create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'MAINTENANCE-02',
            'capacity' => 4,
            'status' => 'maintenance',
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($admin)
            ->test(ListRestaurantTables::class)
            ->filterTable('status', 'maintenance')
            ->assertCanSeeTableRecords([$maintenance])
            ->assertCanNotSeeTableRecords([$available])
            ->resetTableFilters()
            ->searchTable('AVAILABLE-01')
            ->assertCanSeeTableRecords([$available])
            ->assertCanNotSeeTableRecords([$maintenance]);
    }

    public function test_conference_room_capacity_filter_exposes_non_overlapping_bands(): void
    {
        $table = ConferenceRoomsTable::configure(Table::make($this->createMock(HasTable::class)));

        self::assertSame([
            '1-20' => '1-20',
            '21-50' => '21-50',
            '51-100' => '51-100',
            '100+' => '100+',
        ], $table->getFilter('capacity')?->getOptions());
    }

    public function test_conference_room_empty_state_action_resets_active_filters_and_recovers_rows(): void
    {
        $admin = User::factory()->create(['department' => 'admin']);
        $room = ConferenceRoom::query()->create([
            'name' => 'Recovery Conference Room',
            'description' => 'Room used to verify empty-state recovery.',
            'capacity' => 20,
            'price_per_hour' => 500,
            'is_available' => true,
            'is_published' => true,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($admin)
            ->test(ListConferenceRooms::class)
            ->filterTable('capacity', '100+')
            ->assertCanNotSeeTableRecords([$room])
            ->callTableAction('resetFilters')
            ->assertCanSeeTableRecords([$room]);
    }

    public static function operationalFilters(): array
    {
        return [
            'restaurants' => [RestaurantsTable::class, ['is_open', 'is_published']],
            'restaurant tables' => [RestaurantTablesTable::class, ['restaurant', 'status', 'capacity']],
            'menu items' => [MenuItemsTable::class, ['category', 'is_available', 'is_featured', 'is_published']],
            'menu categories' => [MenuCategoriesTable::class, ['is_active', 'is_published']],
            'restaurant order items' => [RestaurantOrderItemsTable::class, ['order', 'menu_item']],
            'guests' => [GuestsTable::class, ['corporate_account', 'created_at']],
            'restaurant reservations' => [RestaurantReservationsTable::class, ['restaurant', 'table', 'reservation_date', 'status', 'payment_status']],
            'conference rooms' => [ConferenceRoomsTable::class, ['is_available', 'is_published', 'capacity']],
        ];
    }
}
