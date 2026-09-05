<?php

namespace Tests\Feature;

use App\Models\KitchenProduction;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\RestaurantOrder;
use App\Services\KitchenProductionReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KitchenProductionClosingBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_closing_balance_carries_forward_production_and_sales_from_before_the_selected_period(): void
    {
        $item = $this->trackedMenuItem();
        $this->production($item, '2026-07-20', produced: 10, wasted: 0);
        $this->restaurantSale($item, '2026-07-25 12:00:00', quantity: 2);
        $this->production($item, '2026-08-05', produced: 3, wasted: 1);
        $this->restaurantSale($item, '2026-08-10 12:00:00', quantity: 3);

        $report = app(KitchenProductionReportService::class)->build(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31'),
        );
        $row = $report['rows']->sole();

        self::assertArrayHasKey('opening_balance', $row);
        self::assertSame(8.0, $row['opening_balance']);
        self::assertSame(-1.0, $row['period_variance']);
        self::assertSame(7.0, $row['closing_balance']);
        self::assertSame('low', $row['stock_status']);
        self::assertSame(1, $report['summary']['low_stock_items']);
        self::assertSame(1, $report['summary']['negative_variance_items']);
    }

    public function test_consumption_is_attributed_to_the_stock_deduction_date_instead_of_the_order_creation_date(): void
    {
        $item = $this->trackedMenuItem();
        $this->production($item, '2026-08-05', produced: 10, wasted: 0);
        $this->restaurantSale(
            $item,
            createdAt: '2026-07-25 12:00:00',
            quantity: 2,
            stockDeductedAt: '2026-08-10 12:00:00',
        );

        $report = app(KitchenProductionReportService::class)->build(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31'),
        );
        $row = $report['rows']->sole();

        self::assertSame(0.0, $row['opening_balance']);
        self::assertSame(2.0, $row['production_amount_sold']);
        self::assertSame(8.0, $row['period_variance']);
        self::assertSame(8.0, $row['closing_balance']);
    }

    public function test_pending_corporate_credit_consumption_is_included_when_stock_is_deducted(): void
    {
        $item = $this->trackedMenuItem();
        $this->production($item, '2026-08-05', produced: 10, wasted: 0);
        $this->restaurantSale(
            $item,
            createdAt: '2026-08-10 12:00:00',
            quantity: 2,
            stockDeductedAt: '2026-08-10 13:00:00',
            paymentStatus: 'pending',
            paymentMethod: 'corporate_account',
        );

        $report = app(KitchenProductionReportService::class)->build(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31'),
        );
        $row = $report['rows']->sole();

        self::assertSame(2, $row['sold_units']);
        self::assertSame(2.0, $row['production_amount_sold']);
        self::assertSame(8.0, $row['closing_balance']);
    }

    public function test_a_stock_reversal_restores_finished_food_in_the_reversal_period(): void
    {
        $item = $this->trackedMenuItem();
        $this->production($item, '2026-07-20', produced: 10, wasted: 0);
        $this->restaurantSale(
            $item,
            createdAt: '2026-07-25 12:00:00',
            quantity: 2,
            stockDeductedAt: '2026-07-25 14:00:00',
            stockReversedAt: '2026-08-10 09:00:00',
        );

        $report = app(KitchenProductionReportService::class)->build(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31'),
        );
        $row = $report['rows']->sole();

        self::assertSame(8.0, $row['opening_balance']);
        self::assertSame(-2.0, $row['production_amount_sold']);
        self::assertSame(2.0, $row['period_variance']);
        self::assertSame(10.0, $row['closing_balance']);
    }

    private function trackedMenuItem(): MenuItem
    {
        $category = MenuCategory::create([
            'name' => 'Closing Balance Category',
            'slug' => 'closing-balance-category',
        ]);

        return MenuItem::create([
            'menu_category_id' => $category->getKey(),
            'name' => 'Closing Balance Meal',
            'slug' => 'closing-balance-meal',
            'price' => 20,
            'tracks_kitchen_production' => true,
            'production_unit' => 'portion',
            'production_usage_per_sale' => 1,
            'low_stock_threshold' => 7.5,
        ]);
    }

    private function production(MenuItem $item, string $date, float $produced, float $wasted): void
    {
        KitchenProduction::create([
            'menu_item_id' => $item->getKey(),
            'production_date' => $date,
            'quantity_produced' => $produced,
            'quantity_wasted' => $wasted,
        ]);
    }

    private function restaurantSale(
        MenuItem $item,
        string $createdAt,
        int $quantity,
        ?string $stockDeductedAt = null,
        ?string $stockReversedAt = null,
        string $paymentStatus = 'completed',
        ?string $paymentMethod = null,
    ): void {
        $order = RestaurantOrder::create([
            'order_number' => 'BALANCE-'.str()->upper(str()->random(10)),
            'ordering_channel' => 'web',
            'subtotal' => $quantity * 20,
            'total' => $quantity * 20,
            'status' => 'served',
            'payment_status' => $paymentStatus,
            'payment_method' => $paymentMethod,
            'stock_deducted_at' => $stockDeductedAt ?? $createdAt,
            'stock_reversed_at' => $stockReversedAt,
        ]);
        $order->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();
        $order->items()->create([
            'menu_item_id' => $item->getKey(),
            'item_name' => $item->name,
            'production_unit' => $item->production_unit,
            'production_usage_per_sale' => 1,
            'quantity' => $quantity,
            'unit_price' => 20,
            'total_price' => $quantity * 20,
        ]);
    }
}
