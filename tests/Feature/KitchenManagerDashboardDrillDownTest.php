<?php

namespace Tests\Feature;

use App\Filament\Admin\Concerns\InteractsWithReportPeriod;
use App\Filament\Admin\Resources\Ingredients\Tables\IngredientsTable;
use App\Filament\Admin\Widgets\KitchenManagerStats;
use App\Filament\Admin\Widgets\KitchenProductionStats;
use App\Filament\Admin\Widgets\KitchenStockStats;
use App\Models\Ingredient;
use App\Models\Restaurant;
use Filament\Facades\Filament;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class KitchenManagerDashboardDrillDownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_operations_cards_link_to_date_filtered_source_records(): void
    {
        $stats = $this->stats(new KitchenManagerStats);

        $this->assertFilteredLink($stats['Orders waiting to start'], '/admin/restaurant-orders', [
            'filters.status.value' => 'confirmed',
            'filters.created_at.created_from' => '2026-08-01',
            'filters.created_at.created_until' => '2026-08-15',
        ]);
        $this->assertFilteredLink($stats['Orders preparing'], '/admin/restaurant-orders', [
            'filters.status.value' => 'preparing',
        ]);
        $this->assertFilteredLink($stats['Orders ready to serve'], '/admin/restaurant-orders', [
            'filters.status.value' => 'ready',
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

    public function test_finished_food_cards_link_to_the_report_and_paid_order_records(): void
    {
        $stats = $this->stats(new KitchenProductionStats);

        foreach (['Tracked Food Items', 'Low finished-food balances', 'Negative Variances'] as $label) {
            $this->assertFilteredLink($stats[$label], '/admin/kitchen-production-report', [
                'period' => 'custom',
                'startDate' => '2026-08-01',
                'endDate' => '2026-08-15',
            ]);
        }

        $this->assertFilteredLink($stats['Food Revenue'], '/admin/restaurant-orders', [
            'filters.payment_status.value' => 'completed',
            'filters.created_at.created_from' => '2026-08-01',
            'filters.created_at.created_until' => '2026-08-15',
        ]);
    }

    public function test_ingredient_cards_link_to_their_exact_current_stock_scopes(): void
    {
        $stats = $this->stats(new KitchenStockStats);

        $this->assertFilteredLink($stats['Out of stock'], '/admin/ingredients', [
            'filters.stock_status.value' => 'out',
        ]);
        $this->assertFilteredLink($stats['Low stock'], '/admin/ingredients', [
            'filters.stock_status.value' => 'low',
        ]);
        $this->assertFilteredLink($stats['Healthy stock'], '/admin/ingredients', [
            'filters.stock_status.value' => 'healthy',
        ]);
        $this->assertFilteredLink($stats['Current inventory value'], '/admin/ingredients', [
            'filters.stock_status.value' => 'active',
        ]);
    }

    public function test_ingredient_stock_filter_matches_the_non_overlapping_card_scopes(): void
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Drill-down Restaurant',
            'description' => 'Restaurant fixture for kitchen stock drill-downs.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $this->ingredient($restaurant, 'Out', 0, 2);
        $this->ingredient($restaurant, 'Low', 2, 2);
        $this->ingredient($restaurant, 'Healthy', 5, 2);
        $this->ingredient($restaurant, 'Inactive', 100, 2, false);
        $table = IngredientsTable::configure(Table::make($this->createMock(HasTable::class)));
        $filter = $table->getFilter('stock_status');

        self::assertNotNull($filter);
        self::assertSame(['Out'], $filter->apply(Ingredient::query(), ['value' => 'out'])->pluck('name')->all());
        self::assertSame(['Low'], $filter->apply(Ingredient::query(), ['value' => 'low'])->pluck('name')->all());
        self::assertSame(['Healthy'], $filter->apply(Ingredient::query(), ['value' => 'healthy'])->pluck('name')->all());
        self::assertEqualsCanonicalizing(
            ['Out', 'Low', 'Healthy'],
            $filter->apply(Ingredient::query(), ['value' => 'active'])->pluck('name')->all(),
        );
    }

    public function test_report_period_accepts_the_dashboard_drill_down_query(): void
    {
        Livewire::withQueryParams([
            'period' => 'custom',
            'startDate' => '2026-08-01',
            'endDate' => '2026-08-15',
        ])->test(KitchenReportPeriodUrlHarness::class)
            ->assertSet('period', 'custom')
            ->assertSet('startDate', '2026-08-01')
            ->assertSet('endDate', '2026-08-15')
            ->assertSet('draftPeriod', 'custom')
            ->assertSet('draftStartDate', '2026-08-01')
            ->assertSet('draftEndDate', '2026-08-15');
    }

    /**
     * @return array<string, Stat>
     */
    private function stats(object $widget): array
    {
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
     * @param  array<string, string>  $expectedQuery
     */
    private function assertFilteredLink(Stat $stat, string $path, array $expectedQuery): void
    {
        $url = (string) $stat->getUrl();
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertSame($path, parse_url($url, PHP_URL_PATH));
        self::assertSame('heroicon-m-arrow-top-right-on-square', $stat->getDescriptionIcon());

        foreach ($expectedQuery as $key => $value) {
            self::assertSame($value, data_get($query, $key), $key);
        }
    }

    private function ingredient(
        Restaurant $restaurant,
        string $name,
        float $stock,
        float $reorderLevel,
        bool $active = true,
    ): Ingredient {
        return Ingredient::query()->create([
            'restaurant_id' => $restaurant->getKey(),
            'name' => $name,
            'unit' => 'kg',
            'current_stock' => $stock,
            'reorder_level' => $reorderLevel,
            'unit_cost' => 1,
            'is_active' => $active,
        ]);
    }
}

class KitchenReportPeriodUrlHarness extends Component
{
    use InteractsWithReportPeriod;

    public function render(): string
    {
        return '<div></div>';
    }
}
