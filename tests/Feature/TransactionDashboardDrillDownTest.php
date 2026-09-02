<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\CorporateReceivables;
use App\Filament\Admin\Resources\Bookings\Pages\ListBookings;
use App\Filament\Admin\Resources\RestaurantReservations\Pages\ListRestaurantReservations;
use App\Filament\Admin\Widgets\TransactionOverview;
use App\Filament\Admin\Widgets\TransactionStats;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Restaurant;
use App\Models\RestaurantReservation;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class TransactionDashboardDrillDownTest extends TestCase
{
    use RefreshDatabase;

    private const FILTERS = [
        'period' => 'monthly',
        'start_date' => '2026-08-01',
        'end_date' => '2026-08-15',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_transaction_stats_link_to_matching_payment_receivable_and_detail_scopes(): void
    {
        $this->actingAs(User::factory()->create(['department' => 'accounting']));
        $stats = $this->stats();

        $this->assertDashboardSectionLink($stats['All transactions created'], 'transaction-mix');
        $this->assertDashboardSectionLink($stats['Active transaction value'], 'transaction-breakdown');
        $this->assertFilteredLink($stats['Completed payments recorded'], '/admin/payments', [
            'filters.transaction_type' => 'all',
            'filters.payment_status' => 'collected',
            'filters.start_date' => '2026-08-01',
            'filters.end_date' => '2026-08-15',
        ]);
        $this->assertDashboardSectionLink($stats['Outstanding from period'], 'outstanding-follow-up');
        $this->assertFilteredLink($stats['Corporate outstanding from period'], '/admin/corporate-receivables', [
            'transaction_type' => 'all',
            'from_date' => '2026-08-01',
            'until_date' => '2026-08-15',
        ]);
    }

    public function test_overview_links_each_channel_and_financial_card_to_an_authorized_destination(): void
    {
        $accountant = User::factory()->create(['department' => 'accounting']);
        $this->actingAs($accountant);
        $overview = $this->overview();
        $rows = collect($overview['rows'])->keyBy('label');

        $this->assertFilteredUrl((string) data_get($rows, 'Hotel bookings.url'), '/admin/bookings', [
            'filters.created_at.created_from' => '2026-08-01',
            'filters.created_at.created_until' => '2026-08-15',
        ]);
        $this->assertFilteredUrl((string) data_get($rows, 'Conference bookings.url'), '/admin/booking-calendar', [
            'type' => 'conference',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-15',
        ]);
        $this->assertFilteredUrl((string) data_get($rows, 'Table reservations.url'), '/admin/payments', [
            'filters.transaction_type' => 'table_reservations',
            'filters.start_date' => '2026-08-01',
            'filters.end_date' => '2026-08-15',
        ]);
        $this->assertFilteredUrl((string) data_get($rows, 'Food orders.url'), '/admin/payments', [
            'filters.transaction_type' => 'food_orders',
            'filters.start_date' => '2026-08-01',
            'filters.end_date' => '2026-08-15',
        ]);
        $this->assertFilteredUrl((string) data_get($overview, 'links.refunds'), '/admin/payments', [
            'filters.payment_status' => 'refunded',
        ]);
        $this->assertFilteredUrl((string) data_get($overview, 'links.corporate'), '/admin/corporate-receivables', [
            'from_date' => '2026-08-01',
            'until_date' => '2026-08-15',
        ]);

        Livewire::actingAs($accountant)
            ->test(TransactionOverview::class, ['pageFilters' => self::FILTERS])
            ->assertSeeHtml('aria-label="Open Hotel bookings"')
            ->assertSeeHtml('aria-label="Open Conference bookings"')
            ->assertSeeHtml('aria-label="Open Table reservations"')
            ->assertSeeHtml('aria-label="Open Food orders"')
            ->assertSee('View details');
    }

    public function test_transaction_links_fall_back_to_dashboard_details_when_the_destination_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['department' => 'management']));
        $stats = $this->stats();

        $this->assertDashboardSectionLink($stats['Completed payments recorded'], 'collection-performance');
        $this->assertDashboardSectionLink($stats['Corporate outstanding from period'], 'outstanding-follow-up');
    }

    public function test_transaction_creation_filters_show_only_records_created_in_the_selected_period(): void
    {
        [$guest, $room, $restaurant, $table] = $this->reservationFixture();
        $insideBooking = $this->createdAt($this->booking($guest, $room), '2026-08-10');
        $outsideBooking = $this->createdAt($this->booking($guest, $room), '2026-07-31');
        $insideReservation = $this->createdAt($this->reservation($guest, $restaurant, $table), '2026-08-10');
        $outsideReservation = $this->createdAt($this->reservation($guest, $restaurant, $table), '2026-08-16');
        $admin = User::factory()->create(['department' => 'admin']);
        $query = [
            'filters' => [
                'created_at' => [
                    'created_from' => '2026-08-01',
                    'created_until' => '2026-08-15',
                ],
            ],
        ];

        Livewire::withQueryParams($query)
            ->actingAs($admin)
            ->test(ListBookings::class)
            ->assertCanSeeTableRecords([$insideBooking])
            ->assertCanNotSeeTableRecords([$outsideBooking]);

        Livewire::withQueryParams($query)
            ->actingAs($admin)
            ->test(ListRestaurantReservations::class)
            ->assertCanSeeTableRecords([$insideReservation])
            ->assertCanNotSeeTableRecords([$outsideReservation]);
    }

    public function test_corporate_receivables_applies_a_valid_dashboard_drill_down_scope(): void
    {
        $accountant = User::factory()->create(['department' => 'accounting']);

        Livewire::withQueryParams([
            'transaction_type' => 'booking',
            'from_date' => '2026-08-01',
            'until_date' => '2026-08-15',
        ])->actingAs($accountant)
            ->test(CorporateReceivables::class)
            ->assertSet('transactionType', 'booking')
            ->assertSet('draftTransactionType', 'booking')
            ->assertSet('fromDate', '2026-08-01')
            ->assertSet('draftFromDate', '2026-08-01')
            ->assertSet('untilDate', '2026-08-15')
            ->assertSet('draftUntilDate', '2026-08-15');
    }

    /**
     * @return array<string, Stat>
     */
    private function stats(): array
    {
        $widget = new TransactionStats;
        $widget->pageFilters = self::FILTERS;
        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return collect($method->invoke($widget))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat])
            ->all();
    }

    /**
     * @return array{rows: array<int, array<string, int|float|string>>, periodLabel: string, totals: array<string, int|float>, links?: array<string, string>}
     */
    private function overview(): array
    {
        $widget = new TransactionOverview;
        $widget->pageFilters = self::FILTERS;
        $method = new ReflectionMethod($widget, 'getViewData');
        $method->setAccessible(true);

        return $method->invoke($widget);
    }

    private function assertDashboardSectionLink(Stat $stat, string $fragment): void
    {
        $url = (string) $stat->getUrl();

        self::assertSame('/admin/transaction-dashboard', parse_url($url, PHP_URL_PATH));
        self::assertSame($fragment, parse_url($url, PHP_URL_FRAGMENT));
        self::assertSame('heroicon-m-arrow-down', $stat->getDescriptionIcon());
    }

    /**
     * @param  array<string, string>  $expectedQuery
     */
    private function assertFilteredLink(Stat $stat, string $path, array $expectedQuery): void
    {
        $this->assertFilteredUrl((string) $stat->getUrl(), $path, $expectedQuery);
        self::assertSame('heroicon-m-arrow-top-right-on-square', $stat->getDescriptionIcon());
    }

    /**
     * @param  array<string, string>  $expectedQuery
     */
    private function assertFilteredUrl(string $url, string $path, array $expectedQuery): void
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertSame($path, parse_url($url, PHP_URL_PATH));

        foreach ($expectedQuery as $key => $value) {
            self::assertSame($value, data_get($query, $key), $key);
        }
    }

    /**
     * @return array{Guest, Room, Restaurant, RestaurantTable}
     */
    private function reservationFixture(): array
    {
        $guest = Guest::query()->create([
            'first_name' => 'Actionable',
            'last_name' => 'Guest',
            'email' => 'actionable-transaction@example.test',
            'phone_number' => '0240000000',
        ]);
        $roomType = RoomType::query()->create([
            'name' => 'Actionable Room Type',
            'price_per_night' => 100,
            'capacity' => 2,
        ]);
        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'ACTION-1',
            'status' => 'available',
        ]);
        $restaurant = Restaurant::query()->create([
            'name' => 'Actionable Restaurant',
            'description' => 'Restaurant fixture for transaction dashboard drill-down tests.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $table = RestaurantTable::query()->create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'ACTION-T1',
            'capacity' => 4,
        ]);

        return [$guest, $room, $restaurant, $table];
    }

    private function booking(Guest $guest, Room $room): Booking
    {
        return Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-11',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);
    }

    private function reservation(
        Guest $guest,
        Restaurant $restaurant,
        RestaurantTable $table,
    ): RestaurantReservation {
        return RestaurantReservation::query()->create([
            'restaurant_id' => $restaurant->id,
            'restaurant_table_id' => $table->id,
            'guest_id' => $guest->id,
            'guest_name' => $guest->full_name,
            'guest_email' => $guest->email,
            'guest_phone' => $guest->phone_number,
            'reservation_date' => '2026-10-10',
            'reservation_time' => '18:00',
            'number_of_guests' => 2,
            'reservation_fee' => 50,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);
    }

    /**
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @return TModel
     */
    private function createdAt(Model $model, string $createdAt): Model
    {
        $model->forceFill([
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ])->saveQuietly();

        return $model;
    }
}
