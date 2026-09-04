<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\KitchenStockStats;
use App\Models\Ingredient;
use App\Models\Restaurant;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class KitchenStockStatsUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_cards_show_current_non_overlapping_replenishment_risks_in_one_query(): void
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Risk Metrics Restaurant',
            'description' => 'Restaurant fixture for stock-risk metrics.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $this->ingredient($restaurant, 'Out', stock: 0, reorderLevel: 2, unitCost: 10);
        $this->ingredient($restaurant, 'Low', stock: 2, reorderLevel: 2, unitCost: 3);
        $this->ingredient($restaurant, 'Healthy', stock: 5, reorderLevel: 2, unitCost: 4);
        $this->ingredient($restaurant, 'Inactive', stock: 100, reorderLevel: 2, unitCost: 100, active: false);

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $stats = $this->stats();
            $queryCount = count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
        }

        self::assertSame([
            'Out of stock',
            'Low stock',
            'Healthy stock',
            'Current inventory value',
        ], array_keys($stats));
        self::assertSame('1', $stats['Out of stock']->getValue());
        self::assertSame('1', $stats['Low stock']->getValue());
        self::assertSame('1', $stats['Healthy stock']->getValue());
        self::assertSame('GHS 26.00', $stats['Current inventory value']->getValue());
        self::assertSame(1, $queryCount);
    }

    private function ingredient(
        Restaurant $restaurant,
        string $name,
        float $stock,
        float $reorderLevel,
        float $unitCost,
        bool $active = true,
    ): Ingredient {
        return Ingredient::query()->create([
            'restaurant_id' => $restaurant->getKey(),
            'name' => $name,
            'unit' => 'kg',
            'current_stock' => $stock,
            'reorder_level' => $reorderLevel,
            'unit_cost' => $unitCost,
            'is_active' => $active,
        ]);
    }

    /**
     * @return array<string, Stat>
     */
    private function stats(): array
    {
        $widget = new KitchenStockStats;
        $widget->pageFilters = [
            'period' => 'monthly',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ];

        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return collect($method->invoke($widget))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat])
            ->all();
    }
}
