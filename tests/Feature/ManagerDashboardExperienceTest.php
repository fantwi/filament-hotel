<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use App\Filament\Admin\Widgets\ManagerOperationsStats;
use App\Filament\Admin\Widgets\ManagerStats;
use App\Models\KitchenProduction;
use Filament\Facades\Filament;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class ManagerDashboardExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_active_kitchen_orders_appear_only_once_across_manager_stats_rows(): void
    {
        $summary = $this->stats(new ManagerStats);
        $operations = $this->stats(new ManagerOperationsStats);
        $labels = collect([...array_keys($summary), ...array_keys($operations)])
            ->map(fn (string $label): string => str($label)->lower()->toString());

        self::assertSame([
            'Hotel Arrivals',
            'Conference Events',
            'Restaurant Reservations',
            'Food Orders',
        ], array_keys($summary));
        self::assertSame(1, $labels->filter(fn (string $label): bool => $label === 'active kitchen orders')->count());
    }

    public function test_manager_summary_stats_link_to_date_filtered_operational_pages(): void
    {
        $stats = $this->stats(new ManagerStats);

        $this->assertFilteredLink($stats['Hotel Arrivals'], '/admin/bookings', [
            'filters.check_in.from' => '2026-08-01',
            'filters.check_in.until' => '2026-08-15',
        ]);
        $this->assertFilteredLink($stats['Conference Events'], '/admin/booking-calendar', [
            'type' => 'conference',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-15',
        ]);
        $this->assertFilteredLink($stats['Restaurant Reservations'], '/admin/restaurant-reservations', [
            'filters.reservation_date.from' => '2026-08-01',
            'filters.reservation_date.until' => '2026-08-15',
        ]);
        $this->assertFilteredLink($stats['Food Orders'], '/admin/restaurant-orders', [
            'filters.created_at.created_from' => '2026-08-01',
            'filters.created_at.created_until' => '2026-08-15',
        ]);
    }

    public function test_manager_detail_stats_link_to_their_filtered_source_records(): void
    {
        $stats = $this->stats(new ManagerOperationsStats);

        $this->assertFilteredLink($stats['Active stays'], '/admin/bookings', [
            'filters.active_period.from' => '2026-08-01',
            'filters.active_period.until' => '2026-08-15',
        ]);
        $this->assertFilteredLink($stats['Active kitchen orders'], '/admin/restaurant-orders', [
            'filters.status.value' => 'kitchen_queue',
            'filters.created_at.created_from' => '2026-08-01',
            'filters.created_at.created_until' => '2026-08-15',
        ]);
        $this->assertFilteredLink($stats['Production batches'], '/admin/kitchen-productions', [
            'filters.production_date.from' => '2026-08-01',
            'filters.production_date.until' => '2026-08-15',
        ]);
        $this->assertFilteredLink($stats['Stock movements'], '/admin/kitchen-stock-movements', [
            'filters.occurred_at.from' => '2026-08-01',
            'filters.occurred_at.until' => '2026-08-15',
        ]);
    }

    public function test_production_drill_down_filter_constrains_batches_to_the_selected_dates(): void
    {
        $table = KitchenProductionResource::table(Table::make($this->createMock(HasTable::class)));
        $filter = $table->getFilter('production_date');

        self::assertNotNull($filter);

        $query = $filter->apply(KitchenProduction::query(), [
            'from' => '2026-08-01',
            'until' => '2026-08-15',
        ]);

        self::assertStringContainsString('production_date', $query->toSql());
        self::assertSame(['2026-08-01', '2026-08-15'], $query->getBindings());
    }

    public function test_manager_operations_row_does_not_eagerly_query_the_corporate_overview(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $stats = $this->stats(new ManagerOperationsStats);
        $queries = DB::getQueryLog();

        DB::disableQueryLog();

        self::assertSame([
            'Active stays',
            'Active kitchen orders',
            'Production batches',
            'Stock movements',
        ], array_keys($stats));
        self::assertCount(4, $queries);
    }

    /**
     * @return array<string, Stat>
     */
    private function stats(object $widget): array
    {
        $widget->pageFilters = [
            'period' => 'monthly',
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
     * @param  array<string, string>  $expectedQuery
     */
    private function assertFilteredLink(Stat $stat, string $path, array $expectedQuery = []): void
    {
        $url = (string) $stat->getUrl();
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertSame($path, parse_url($url, PHP_URL_PATH));
        self::assertSame('heroicon-m-arrow-top-right-on-square', $stat->getDescriptionIcon());

        foreach ($expectedQuery as $key => $value) {
            self::assertSame($value, data_get($query, $key), $key);
        }
    }
}
