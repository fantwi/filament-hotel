<?php

namespace Tests\Feature;

use App\Models\KitchenProduction;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\RestaurantOrderItem;
use App\Services\KitchenProductionReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KitchenProductionReportPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_large_report_is_aggregated_without_hydrating_transaction_rows(): void
    {
        $category = MenuCategory::query()->create([
            'name' => 'Performance',
            'slug' => 'performance',
        ]);
        $item = MenuItem::query()->create([
            'menu_category_id' => $category->getKey(),
            'name' => 'Batch Meal',
            'slug' => 'batch-meal',
            'price' => 25,
            'tracks_kitchen_production' => true,
            'production_unit' => 'portion',
            'production_usage_per_sale' => 1,
            'low_stock_threshold' => 5,
        ]);
        $timestamp = Carbon::parse('2026-08-15 12:00:00');

        collect(range(1, 1100))
            ->map(fn (int $index): array => [
                'menu_item_id' => $item->getKey(),
                'batch_reference' => sprintf('PERF-%04d', $index),
                'production_date' => '2026-08-15',
                'quantity_produced' => 2,
                'quantity_wasted' => 0,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->chunk(100)
            ->each(fn ($rows) => DB::table('kitchen_productions')->insert($rows->all()));

        collect(range(1, 1100))
            ->map(fn (int $index): array => [
                'id' => 10000 + $index,
                'order_number' => sprintf('PERF-ORDER-%04d', $index),
                'ordering_channel' => 'web',
                'subtotal' => 25,
                'total' => 25,
                'status' => 'served',
                'payment_status' => 'completed',
                'stock_deducted_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->chunk(100)
            ->each(fn ($rows) => DB::table('restaurant_orders')->insert($rows->all()));

        collect(range(1, 1100))
            ->map(fn (int $index): array => [
                'restaurant_order_id' => 10000 + $index,
                'menu_item_id' => $item->getKey(),
                'item_name' => $item->name,
                'production_unit' => 'portion',
                'production_usage_per_sale' => 1,
                'quantity' => 1,
                'unit_price' => 25,
                'total_price' => 25,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->chunk(100)
            ->each(fn ($rows) => DB::table('restaurant_order_items')->insert($rows->all()));

        $hydratedProductions = 0;
        $hydratedOrderItems = 0;
        KitchenProduction::retrieved(function () use (&$hydratedProductions): void {
            $hydratedProductions++;
        });
        RestaurantOrderItem::retrieved(function () use (&$hydratedOrderItems): void {
            $hydratedOrderItems++;
        });

        DB::flushQueryLog();
        DB::enableQueryLog();

        $report = app(KitchenProductionReportService::class)->build(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31'),
        );
        $queryCount = count(DB::getQueryLog());

        DB::disableQueryLog();

        $row = $report['rows']->sole();

        self::assertSame(2200.0, $row['produced']);
        self::assertSame(1100, $row['sold_units']);
        self::assertSame(1100.0, $row['closing_balance']);
        self::assertSame(0, $hydratedProductions);
        self::assertSame(0, $hydratedOrderItems);
        self::assertLessThanOrEqual(5, $queryCount);
    }

    public function test_report_event_indexes_are_installed(): void
    {
        foreach ($this->reportIndexDefinitions() as $table => $expectedIndexes) {
            $installedIndexes = collect(Schema::getIndexes($table))->keyBy('name');

            foreach ($expectedIndexes as $name => $columns) {
                self::assertSame($columns, $installedIndexes->get($name)['columns'] ?? null);
            }
        }
    }

    public function test_report_event_index_migration_is_reversible(): void
    {
        $path = database_path('migrations/2026_09_05_000000_add_kitchen_production_report_indexes.php');

        self::assertFileExists($path);

        $migration = require $path;
        $migration->down();

        foreach ($this->reportIndexDefinitions() as $table => $expectedIndexes) {
            foreach (array_keys($expectedIndexes) as $name) {
                self::assertNotContains($name, Schema::getIndexListing($table));
            }
        }

        $migration->up();

        foreach ($this->reportIndexDefinitions() as $table => $expectedIndexes) {
            $installedIndexes = collect(Schema::getIndexes($table))->keyBy('name');

            foreach ($expectedIndexes as $name => $columns) {
                self::assertSame($columns, $installedIndexes->get($name)['columns'] ?? null);
            }
        }
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    private function reportIndexDefinitions(): array
    {
        return [
            'kitchen_productions' => [
                'kitchen_productions_report_period_index' => ['production_date', 'menu_item_id'],
            ],
            'restaurant_orders' => [
                'restaurant_orders_stock_deducted_report_index' => ['stock_deducted_at', 'id'],
                'restaurant_orders_stock_reversed_report_index' => ['stock_reversed_at', 'id'],
            ],
        ];
    }
}
