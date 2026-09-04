<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\RestaurantOrders\Tables\RestaurantOrdersTable;
use App\Filament\Admin\Widgets\KitchenStaffStats;
use App\Models\RestaurantOrder;
use Filament\Facades\Filament;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class KitchenStaffDashboardDrillDownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_live_workload_cards_link_to_current_statuses_without_date_filters(): void
    {
        $stats = $this->stats();

        foreach ([
            'Orders waiting to start' => 'confirmed',
            'Orders preparing' => 'preparing',
            'Orders ready to serve' => 'ready',
        ] as $label => $status) {
            $query = $this->assertFilteredLink($stats[$label]);

            self::assertSame($status, data_get($query, 'filters.status.value'));
            self::assertArrayNotHasKey('created_at', $query['filters']);
            self::assertArrayNotHasKey('served_at', $query['filters']);
        }
    }

    public function test_served_card_links_to_orders_served_in_the_selected_period(): void
    {
        $query = $this->assertFilteredLink($this->stats()['Orders served']);

        self::assertSame('served', data_get($query, 'filters.status.value'));
        self::assertSame('2026-08-01', data_get($query, 'filters.served_at.from'));
        self::assertSame('2026-08-15', data_get($query, 'filters.served_at.until'));
        self::assertArrayNotHasKey('created_at', $query['filters']);
    }

    public function test_served_date_filter_returns_only_orders_served_in_the_selected_period(): void
    {
        $this->order('SERVED-BEFORE', '2026-07-31 23:59:59');
        $this->order('SERVED-IN-PERIOD', '2026-08-10 12:00:00');
        $this->order('SERVED-AFTER', '2026-08-16 00:00:00');

        $table = RestaurantOrdersTable::configure(Table::make($this->createMock(HasTable::class)));
        $filter = $table->getFilter('served_at');

        self::assertNotNull($filter);
        self::assertSame(
            ['SERVED-IN-PERIOD'],
            $filter->apply(RestaurantOrder::query(), [
                'from' => '2026-08-01',
                'until' => '2026-08-15',
            ])->pluck('order_number')->all(),
        );
    }

    /**
     * @return array<string, Stat>
     */
    private function stats(): array
    {
        $widget = new KitchenStaffStats;
        $widget->pageFilters = [
            'period' => 'custom',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-15',
        ];
        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return collect($method->invoke($widget))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function assertFilteredLink(Stat $stat): array
    {
        $url = (string) $stat->getUrl();
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertSame('/admin/restaurant-orders', parse_url($url, PHP_URL_PATH));
        self::assertSame('heroicon-m-arrow-top-right-on-square', $stat->getDescriptionIcon());

        return $query;
    }

    private function order(string $number, string $servedAt): RestaurantOrder
    {
        return RestaurantOrder::query()->create([
            'order_number' => $number,
            'ordering_channel' => 'web',
            'subtotal' => 20,
            'total' => 20,
            'status' => 'served',
            'payment_status' => 'completed',
            'served_at' => $servedAt,
        ]);
    }
}
