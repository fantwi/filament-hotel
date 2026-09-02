<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\KitchenStockStats;
use App\Models\Ingredient;
use App\Models\KitchenStockMovement;
use App\Models\Restaurant;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class KitchenStockStatsUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_cards_report_movement_counts_instead_of_adding_incompatible_units(): void
    {
        [$rice, $oil] = $this->ingredients();

        $this->movement($rice, KitchenStockMovement::TYPE_RECEIPT, KitchenStockMovement::DIRECTION_IN, 10);
        $this->movement($rice, KitchenStockMovement::TYPE_ADJUSTMENT_IN, KitchenStockMovement::DIRECTION_IN, 5);
        $this->movement($oil, KitchenStockMovement::TYPE_RECEIPT, KitchenStockMovement::DIRECTION_IN, 3);
        $this->movement($rice, KitchenStockMovement::TYPE_CONSUMPTION, KitchenStockMovement::DIRECTION_OUT, 4);
        $this->movement($oil, KitchenStockMovement::TYPE_CONSUMPTION, KitchenStockMovement::DIRECTION_OUT, 1.25);
        $this->movement($rice, KitchenStockMovement::TYPE_WASTAGE, KitchenStockMovement::DIRECTION_OUT, 0.5);

        $stats = $this->stats();

        self::assertSame([
            'Ingredients Moved',
            'Inbound Movements',
            'Consumption Movements',
            'Wastage Movements',
        ], array_keys($stats));
        self::assertSame('2', $stats['Ingredients Moved']->getValue());
        self::assertSame('3', $stats['Inbound Movements']->getValue());
        self::assertSame('2', $stats['Consumption Movements']->getValue());
        self::assertSame('1', $stats['Wastage Movements']->getValue());
    }

    public function test_stock_card_descriptions_group_quantities_by_ingredient_unit(): void
    {
        [$rice, $oil] = $this->ingredients();

        $this->movement($rice, KitchenStockMovement::TYPE_RECEIPT, KitchenStockMovement::DIRECTION_IN, 10);
        $this->movement($rice, KitchenStockMovement::TYPE_ADJUSTMENT_IN, KitchenStockMovement::DIRECTION_IN, 5);
        $this->movement($oil, KitchenStockMovement::TYPE_RECEIPT, KitchenStockMovement::DIRECTION_IN, 3);
        $this->movement($rice, KitchenStockMovement::TYPE_CONSUMPTION, KitchenStockMovement::DIRECTION_OUT, 4);
        $this->movement($oil, KitchenStockMovement::TYPE_CONSUMPTION, KitchenStockMovement::DIRECTION_OUT, 1.25);
        $this->movement($rice, KitchenStockMovement::TYPE_WASTAGE, KitchenStockMovement::DIRECTION_OUT, 0.5);
        $this->movement($oil, KitchenStockMovement::TYPE_RECEIPT, KitchenStockMovement::DIRECTION_IN, 99, '2026-09-01 09:00:00');

        $stats = $this->stats();

        self::assertArrayHasKey('Inbound Movements', $stats);
        self::assertSame('15 kg · 3 litres across 3 movements', $stats['Inbound Movements']->getDescription());
        self::assertSame('4 kg · 1.25 litres across 2 movements', $stats['Consumption Movements']->getDescription());
        self::assertSame('0.5 kg across 1 movement', $stats['Wastage Movements']->getDescription());
    }

    /**
     * @return array{Ingredient, Ingredient}
     */
    private function ingredients(): array
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Stock Metrics Restaurant',
            'description' => 'Restaurant fixture for stock-unit metrics.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $rice = Ingredient::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Rice',
            'unit' => 'kg',
        ]);
        $oil = Ingredient::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Oil',
            'unit' => 'litres',
        ]);

        return [$rice, $oil];
    }

    private function movement(
        Ingredient $ingredient,
        string $type,
        string $direction,
        float $quantity,
        string $occurredAt = '2026-08-10 09:00:00',
    ): void {
        KitchenStockMovement::query()->create([
            'ingredient_id' => $ingredient->id,
            'type' => $type,
            'direction' => $direction,
            'quantity' => $quantity,
            'balance_before' => 0,
            'balance_after' => $direction === KitchenStockMovement::DIRECTION_IN ? $quantity : -$quantity,
            'occurred_at' => $occurredAt,
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
