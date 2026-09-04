<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Bookings\Tables\BookingsTable;
use App\Filament\Admin\Resources\ConferenceRooms\Tables\ConferenceRoomsTable;
use App\Filament\Admin\Resources\MenuItems\Tables\MenuItemsTable;
use App\Filament\Admin\Resources\Payments\Tables\PaymentsTable;
use App\Filament\Admin\Resources\RestaurantReservations\Tables\RestaurantReservationsTable;
use App\Filament\Admin\Resources\Restaurants\Tables\RestaurantsTable;
use App\Filament\Admin\Resources\RestaurantTables\Tables\RestaurantTablesTable;
use App\Filament\Admin\Resources\RoomTypes\Tables\RoomTypesTable;
use App\Models\RoomType;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FilamentResponsiveTablesTest extends TestCase
{
    /**
     * @param  class-string  $tableClass
     * @param  list<string>  $hiddenByDefault
     * @param  list<string>  $visibleByDefault
     */
    #[DataProvider('responsiveTables')]
    public function test_secondary_columns_are_optional_while_operational_columns_remain_visible(
        string $tableClass,
        array $hiddenByDefault,
        array $visibleByDefault,
    ): void {
        $table = $tableClass::configure(Table::make($this->createMock(HasTable::class)));

        foreach ($hiddenByDefault as $columnName) {
            $column = $table->getColumn($columnName);

            self::assertNotNull($column, "Missing [{$columnName}] column on [{$tableClass}].");
            self::assertTrue($column->isToggleable(), "[{$columnName}] should be toggleable on [{$tableClass}].");
            self::assertTrue(
                $column->isToggledHiddenByDefault(),
                "[{$columnName}] should be hidden by default on [{$tableClass}].",
            );
        }

        foreach ($visibleByDefault as $columnName) {
            $column = $table->getColumn($columnName);

            self::assertNotNull($column, "Missing [{$columnName}] column on [{$tableClass}].");
            self::assertFalse(
                $column->isToggledHiddenByDefault(),
                "Operational column [{$columnName}] should remain visible on [{$tableClass}].",
            );
        }
    }

    public function test_conference_room_descriptions_are_bounded_and_prices_use_ghs_formatting(): void
    {
        $table = ConferenceRoomsTable::configure(Table::make($this->createMock(HasTable::class)));
        $description = $table->getColumn('description');
        $price = $table->getColumn('price_per_hour');

        self::assertNotNull($description);
        self::assertSame(80, $description->getCharacterLimit());
        self::assertTrue($description->canWrap());
        self::assertNotNull($price);
        self::assertTrue($price->isMoney());
        self::assertStringContainsString('GHS', (string) $price->formatState(100));
        self::assertTrue($table->getColumn('capacity')?->isNumeric());
    }

    public function test_room_types_table_replaces_desktop_columns_with_a_mobile_summary(): void
    {
        $table = RoomTypesTable::configure(Table::make($this->createMock(HasTable::class)));
        $mobileSummary = $table->getColumn('mobile_summary');

        self::assertInstanceOf(TextColumn::class, $mobileSummary);
        self::assertSame('md', $mobileSummary->getHiddenFrom());

        foreach (['image', 'name', 'price_per_night', 'capacity', 'rooms_count', 'facilities.name', 'created_at', 'updated_at'] as $columnName) {
            self::assertSame(
                'md',
                $table->getColumn($columnName)?->getVisibleFrom(),
                "[{$columnName}] should collapse below the medium breakpoint.",
            );
        }

        $publication = $table->getColumn('is_published');

        self::assertInstanceOf(ToggleColumn::class, $publication);
        self::assertNull($publication->getVisibleFrom());
    }

    public function test_room_types_mobile_summary_includes_booking_decision_details(): void
    {
        $roomType = new RoomType([
            'name' => 'Deluxe Suite',
            'price_per_night' => 350,
            'capacity' => 2,
            'is_published' => true,
        ]);
        $roomType->setAttribute('rooms_count', 4);
        $column = RoomTypesTable::configure(Table::make($this->createMock(HasTable::class)))
            ->getColumn('mobile_summary');

        self::assertInstanceOf(TextColumn::class, $column);

        $column->record($roomType)->clearCachedState();

        self::assertSame('Deluxe Suite', $column->getState());
        self::assertSame('GHS 350.00 per night · 2 guests · 4 rooms · Published', $column->getDescriptionBelow());
    }

    public function test_room_types_table_exposes_cover_media_and_ghs_pricing(): void
    {
        $table = RoomTypesTable::configure(Table::make($this->createMock(HasTable::class)));
        $image = $table->getColumn('image');
        $price = $table->getColumn('price_per_night');
        $capacity = $table->getColumn('capacity');

        self::assertInstanceOf(ImageColumn::class, $image);
        self::assertSame('public', $image->getDiskName());
        self::assertSame('public', $image->getVisibility());
        self::assertTrue($image->isSquare());
        self::assertInstanceOf(TextColumn::class, $price);
        self::assertTrue($price->isMoney());
        self::assertStringContainsString('GHS', (string) $price->formatState(350));
        self::assertInstanceOf(TextColumn::class, $capacity);
        self::assertSame(' guests', $capacity->getSuffix());
    }

    public function test_room_types_table_exposes_physical_inventory_and_optional_facilities(): void
    {
        $table = RoomTypesTable::configure(Table::make($this->createMock(HasTable::class)));
        $rooms = $table->getColumn('rooms_count');
        $facilities = $table->getColumn('facilities.name');

        self::assertInstanceOf(TextColumn::class, $rooms);
        self::assertSame('rooms', $rooms->getRelationshipsToCount());
        self::assertSame(' rooms', $rooms->getSuffix());
        self::assertTrue($rooms->isSortable());
        self::assertInstanceOf(TextColumn::class, $facilities);
        self::assertTrue($facilities->isBadge());
        self::assertTrue($facilities->isToggleable());
        self::assertTrue($facilities->isToggledHiddenByDefault());
    }

    public static function responsiveTables(): array
    {
        return [
            'bookings' => [
                BookingsTable::class,
                ['nights', 'total_paid', 'balance'],
                ['guest.full_name', 'room.room_number', 'check_in', 'status'],
            ],
            'restaurant reservations' => [
                RestaurantReservationsTable::class,
                ['id', 'number_of_guests', 'created_at'],
                ['guest_name', 'table.table_number', 'reservation_date', 'status'],
            ],
            'menu items' => [
                MenuItemsTable::class,
                ['image', 'is_featured', 'preparation_time', 'sort_order'],
                ['category.name', 'name', 'price', 'is_available'],
            ],
            'restaurants' => [
                RestaurantsTable::class,
                ['hero_image', 'capacity'],
                ['name', 'opening_time', 'closing_time', 'is_open'],
            ],
            'restaurant tables' => [
                RestaurantTablesTable::class,
                ['image', 'reservation_fee', 'location'],
                ['table_number', 'restaurant.name', 'capacity', 'status'],
            ],
            'payments' => [
                PaymentsTable::class,
                ['transaction_reference', 'created_at'],
                ['transaction_id', 'transaction_guest', 'amount', 'method'],
            ],
            'conference rooms' => [
                ConferenceRoomsTable::class,
                ['id', 'description'],
                ['name', 'capacity', 'price_per_hour', 'is_available', 'is_published'],
            ],
        ];
    }
}
