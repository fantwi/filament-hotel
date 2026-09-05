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

    public function test_sell_through_counts_sales_from_opening_stock(): void
    {
        $item = $this->trackedMenuItem();
        $this->production($item, '2026-07-20', produced: 10, wasted: 0);
        $this->restaurantSale($item, '2026-08-10 12:00:00', quantity: 4);

        $row = app(KitchenProductionReportService::class)->build(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31'),
        )['rows']->sole();

        self::assertSame(40.0, $row['sell_through']);
    }

    public function test_sell_through_uses_opening_stock_and_net_production_as_availability(): void
    {
        $item = $this->trackedMenuItem();
        $this->production($item, '2026-07-20', produced: 10, wasted: 0);
        $this->production($item, '2026-08-05', produced: 6, wasted: 1);
        $this->restaurantSale($item, '2026-07-25 12:00:00', quantity: 2);
        $this->restaurantSale($item, '2026-08-10 12:00:00', quantity: 3);

        $row = app(KitchenProductionReportService::class)->build(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31'),
        )['rows']->sole();

        // Opening balance is 8 and net production is 5, so 3 / 13 = 23.0769%.
        self::assertEqualsWithDelta(23.0769, $row['sell_through'], 0.0001);
    }

    public function test_sell_through_above_one_hundred_percent_exposes_over_consumption(): void
    {
        $item = $this->trackedMenuItem();
        $this->production($item, '2026-07-20', produced: 5, wasted: 0);
        $this->production($item, '2026-08-05', produced: 5, wasted: 0);
        $this->restaurantSale($item, '2026-08-10 12:00:00', quantity: 12);

        $row = app(KitchenProductionReportService::class)->build(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31'),
        )['rows']->sole();

        self::assertSame(120.0, $row['sell_through']);
        self::assertSame(-2.0, $row['closing_balance']);
    }

    public function test_sell_through_is_unavailable_without_positive_available_stock(): void
    {
        $item = $this->trackedMenuItem();
        $this->production($item, '2026-07-20', produced: 5, wasted: 0);
        $this->restaurantSale($item, '2026-07-25 12:00:00', quantity: 7);

        $row = app(KitchenProductionReportService::class)->build(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31'),
        )['rows']->sole();

        self::assertSame(-2.0, $row['opening_balance']);
        self::assertNull($row['sell_through']);
    }

    public function test_sell_through_is_unavailable_during_net_stock_restoration(): void
    {
        $item = $this->trackedMenuItem();
        $this->production($item, '2026-07-20', produced: 10, wasted: 0);
        $this->restaurantSale(
            $item,
            createdAt: '2026-07-25 12:00:00',
            quantity: 2,
            stockReversedAt: '2026-08-10 09:00:00',
        );

        $row = app(KitchenProductionReportService::class)->build(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31'),
        )['rows']->sole();

        self::assertSame(-2.0, $row['production_amount_sold']);
        self::assertNull($row['sell_through']);
    }

    public function test_opening_balance_alone_is_not_selected_period_activity(): void
    {
        $item = $this->trackedMenuItem();
        $this->production($item, '2026-07-20', produced: 10, wasted: 0);

        $report = app(KitchenProductionReportService::class)->build(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31'),
        );

        self::assertSame(10.0, $report['rows']->sole()['opening_balance']);
        self::assertFalse($report['has_period_activity']);
    }

    public function test_net_zero_deduction_and_reversal_are_still_selected_period_activity(): void
    {
        $item = $this->trackedMenuItem();
        $this->restaurantSale(
            $item,
            createdAt: '2026-08-10 12:00:00',
            quantity: 2,
            stockDeductedAt: '2026-08-10 13:00:00',
            stockReversedAt: '2026-08-11 09:00:00',
        );

        $report = app(KitchenProductionReportService::class)->build(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31'),
        );

        self::assertSame(0, $report['rows']->sole()['sold_units']);
        self::assertSame(0.0, $report['rows']->sole()['production_amount_sold']);
        self::assertTrue($report['has_period_activity']);
    }

    public function test_activity_for_an_untracked_item_does_not_activate_the_tracked_report(): void
    {
        $trackedItem = $this->trackedMenuItem();
        $untrackedItem = MenuItem::create([
            'menu_category_id' => $trackedItem->menu_category_id,
            'name' => 'Untracked Meal',
            'slug' => 'untracked-meal',
            'price' => 20,
            'tracks_kitchen_production' => false,
            'production_unit' => 'portion',
            'production_usage_per_sale' => 1,
            'low_stock_threshold' => 5,
        ]);
        $this->production($untrackedItem, '2026-08-10', produced: 10, wasted: 0);

        $report = app(KitchenProductionReportService::class)->build(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31'),
        );

        self::assertCount(1, $report['rows']);
        self::assertFalse($report['has_period_activity']);
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
