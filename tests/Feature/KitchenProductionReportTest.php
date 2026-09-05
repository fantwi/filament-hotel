<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\KitchenProductionReport;
use App\Filament\Admin\Widgets\KitchenProductionReportStats;
use App\Models\KitchenProduction;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KitchenProductionReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_kitchen_report_stats_widget_uses_precomputed_summary_without_queries(): void
    {
        self::assertTrue(is_subclass_of(KitchenProductionReportStats::class, StatsOverviewWidget::class));

        $widget = new KitchenProductionReportStats;
        $widget->summary = [
            'tracked_items' => 6,
            'healthy_items' => 4,
            'low_stock_items' => 2,
            'negative_variance_items' => 1,
            'net_revenue' => 1250.50,
        ];
        $widget->fromDate = '2026-08-01';
        $widget->untilDate = '2026-08-31';
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $stats = $method->invoke($widget);
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
        }

        self::assertCount(0, $queries);
        self::assertCount(5, $stats);
        self::assertSame(
            ['6', '4', '2', '1', 'GHS 1,250.50'],
            array_map(fn ($stat): string => $stat->getValue(), $stats),
        );
        self::assertSame('Aug 1, 2026 - Aug 31, 2026', $stats[0]->getDescription());
        self::assertStringContainsString('Aug 1, 2026 - Aug 31, 2026', $stats[4]->getDescription());
    }

    public function test_kitchen_report_page_calculates_report_only_once(): void
    {
        $this->travelTo('2026-08-15 12:00:00');
        Permission::findOrCreate('view kitchen production reports', 'web');
        $user = User::factory()->create(['department' => 'kitchen']);
        $user->givePermissionTo('view kitchen production reports');
        $category = MenuCategory::query()->create([
            'name' => 'Prepared meals',
            'slug' => 'prepared-meals',
        ]);
        $item = MenuItem::query()->create([
            'menu_category_id' => $category->getKey(),
            'name' => 'Report Query Meal',
            'slug' => 'report-query-meal',
            'price' => 25,
            'tracks_kitchen_production' => true,
            'production_unit' => 'portion',
            'production_usage_per_sale' => 1,
            'low_stock_threshold' => 5,
        ]);
        KitchenProduction::query()->create([
            'menu_item_id' => $item->getKey(),
            'batch_reference' => 'KP-REPORT-QUERY',
            'production_date' => '2026-08-15',
            'quantity_produced' => 10,
            'quantity_wasted' => 1,
        ]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $component = Livewire::actingAs($user)
                ->test(KitchenProductionReport::class)
                ->assertSuccessful();
            $reportQueries = collect(DB::getQueryLog())
                ->pluck('query')
                ->filter(fn (string $query): bool => (bool) preg_match(
                    '/\b(?:from|join)\s+["`]?(?:kitchen_productions|restaurant_order_items|restaurant_orders|payments|menu_items)["`]?\b/i',
                    $query,
                ));
        } finally {
            DB::disableQueryLog();
        }

        self::assertCount(5, $reportQueries);
        self::assertStringContainsString('Report Query Meal', $component->html());
        self::assertStringContainsString('GHS 0.00', $component->html());

        $component
            ->set('draftPeriod', 'custom')
            ->set('draftStartDate', '2026-08-10')
            ->set('draftEndDate', '2026-08-12');

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $component->call('applyReportPeriod')->assertHasNoErrors();
            $refreshedReportQueries = collect(DB::getQueryLog())
                ->pluck('query')
                ->filter(fn (string $query): bool => (bool) preg_match(
                    '/\b(?:from|join)\s+["`]?(?:kitchen_productions|restaurant_order_items|restaurant_orders|payments|menu_items)["`]?\b/i',
                    $query,
                ));
        } finally {
            DB::disableQueryLog();
        }

        self::assertCount(5, $refreshedReportQueries);
        self::assertStringContainsString('Aug 10, 2026 - Aug 12, 2026', $component->html());
        self::assertStringContainsString('Available-stock sell-through', $component->html());
        self::assertStringContainsString('N/A', $component->html());
    }
}
