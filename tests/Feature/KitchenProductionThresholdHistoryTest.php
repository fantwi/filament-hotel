<?php

namespace Tests\Feature;

use App\Models\KitchenProduction;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\MenuItemStockThresholdHistory;
use App\Services\KitchenProductionReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KitchenProductionThresholdHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_items_record_only_effective_low_stock_threshold_changes(): void
    {
        self::assertTrue(
            class_exists(MenuItemStockThresholdHistory::class),
            'Menu-item threshold history has not been implemented.',
        );

        $this->travelTo('2026-07-01 09:00:00');
        $item = $this->trackedMenuItem(threshold: 5);

        self::assertSame(
            [['threshold' => '5.000', 'effective_from' => '2026-07-01 09:00:00']],
            $item->stockThresholdHistory()
                ->get()
                ->map(fn (MenuItemStockThresholdHistory $history): array => [
                    'threshold' => $history->threshold,
                    'effective_from' => $history->effective_from->toDateTimeString(),
                ])
                ->all(),
        );

        $this->travelTo('2026-07-15 10:00:00');
        $item->update(['name' => 'Renamed historical meal']);
        self::assertCount(1, $item->stockThresholdHistory()->get());

        $this->travelTo('2026-09-05 11:30:00');
        $item->update(['low_stock_threshold' => 10]);

        self::assertSame(
            [
                ['threshold' => '5.000', 'effective_from' => '2026-07-01 09:00:00'],
                ['threshold' => '10.000', 'effective_from' => '2026-09-05 11:30:00'],
            ],
            $item->stockThresholdHistory()
                ->orderBy('effective_from')
                ->orderBy('id')
                ->get()
                ->map(fn (MenuItemStockThresholdHistory $history): array => [
                    'threshold' => $history->threshold,
                    'effective_from' => $history->effective_from->toDateTimeString(),
                ])
                ->all(),
        );
    }

    public function test_report_uses_the_threshold_effective_at_the_period_end(): void
    {
        $this->travelTo('2026-07-01 09:00:00');
        $item = $this->trackedMenuItem(threshold: 5);
        KitchenProduction::query()->create([
            'menu_item_id' => $item->getKey(),
            'production_date' => '2026-08-15',
            'quantity_produced' => 7,
            'quantity_wasted' => 0,
        ]);

        $this->travelTo('2026-09-05 11:30:00');
        $item->update(['low_stock_threshold' => 10]);

        $historicalRow = app(KitchenProductionReportService::class)->build(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31'),
        )['rows']->sole();
        $currentRow = app(KitchenProductionReportService::class)->build(
            Carbon::parse('2026-09-01'),
            Carbon::parse('2026-09-30'),
        )['rows']->sole();

        self::assertSame(5.0, $historicalRow['low_stock_threshold']);
        self::assertSame('healthy', $historicalRow['stock_status']);
        self::assertSame(10.0, $currentRow['low_stock_threshold']);
        self::assertSame('low', $currentRow['stock_status']);
    }

    public function test_latest_change_wins_when_thresholds_share_an_effective_timestamp(): void
    {
        $this->travelTo('2026-09-05 11:30:00');
        $item = $this->trackedMenuItem(threshold: 2);
        $item->update(['low_stock_threshold' => 4]);
        $item->update(['low_stock_threshold' => 6]);
        KitchenProduction::query()->create([
            'menu_item_id' => $item->getKey(),
            'production_date' => '2026-09-05',
            'quantity_produced' => 5,
            'quantity_wasted' => 0,
        ]);

        $row = app(KitchenProductionReportService::class)->build(
            Carbon::parse('2026-09-01'),
            Carbon::parse('2026-09-30'),
        )['rows']->sole();

        self::assertCount(3, $item->stockThresholdHistory()->get());
        self::assertSame(6.0, $row['low_stock_threshold']);
        self::assertSame('low', $row['stock_status']);
    }

    public function test_migration_backfills_the_current_threshold_for_existing_menu_items(): void
    {
        $path = database_path('migrations/2026_09_05_000100_create_menu_item_stock_threshold_histories_table.php');
        self::assertFileExists($path);

        $migration = require $path;
        $migration->down();
        $this->travelTo('2026-06-10 08:00:00');
        $item = MenuItem::withoutEvents(fn (): MenuItem => $this->trackedMenuItem(threshold: 7.5));

        $migration->up();

        $history = MenuItemStockThresholdHistory::query()->sole();
        self::assertSame($item->getKey(), $history->menu_item_id);
        self::assertSame('7.500', $history->threshold);
        self::assertSame('2026-06-10 08:00:00', $history->effective_from->toDateTimeString());
    }

    private function trackedMenuItem(float $threshold): MenuItem
    {
        $category = MenuCategory::query()->firstOrCreate(
            ['slug' => 'threshold-history'],
            ['name' => 'Threshold history'],
        );

        return MenuItem::query()->create([
            'menu_category_id' => $category->getKey(),
            'name' => 'Historical threshold meal',
            'slug' => 'historical-threshold-meal-'.str()->lower(str()->random(8)),
            'price' => 25,
            'tracks_kitchen_production' => true,
            'production_unit' => 'portion',
            'production_usage_per_sale' => 1,
            'low_stock_threshold' => $threshold,
        ]);
    }
}
