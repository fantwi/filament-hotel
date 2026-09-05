<?php

namespace Tests\Feature;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Services\KitchenProductionReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KitchenProductionReportFinancialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_collections_follow_payment_dates_and_are_allocated_across_tracked_items(): void
    {
        $category = $this->category();
        $rice = $this->trackedMenuItem($category, 'Jollof Rice', 30);
        $chicken = $this->trackedMenuItem($category, 'Grilled Chicken', 40);
        $order = $this->restaurantOrder('2026-07-20 12:00:00', total: 107);
        $order->items()->createMany([
            $this->orderItem($rice, quantity: 2, unitPrice: 30),
            $this->orderItem($chicken, quantity: 1, unitPrice: 40),
        ]);
        $this->payment($order, amount: 80, createdAt: '2026-08-10 09:00:00');
        $this->payment($order, amount: 27, createdAt: '2026-09-01 09:00:00');

        $report = app(KitchenProductionReportService::class)->build(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31'),
        );
        $rows = $report['rows']->keyBy('name');

        self::assertSame(48.0, $rows['Jollof Rice']['collected_revenue']);
        self::assertSame(32.0, $rows['Grilled Chicken']['collected_revenue']);
        self::assertSame(80.0, $report['summary']['collected_revenue']);
        self::assertSame(0.0, $report['summary']['refunded_revenue']);
        self::assertSame(80.0, $report['summary']['net_revenue']);
    }

    public function test_refunds_follow_the_refund_date_without_erasing_the_original_collection(): void
    {
        $item = $this->trackedMenuItem($this->category(), 'Banku and Tilapia', 100);
        $order = $this->restaurantOrder('2026-07-15 12:00:00', total: 107);
        $order->items()->create($this->orderItem($item, quantity: 1, unitPrice: 100));
        $this->payment(
            $order,
            amount: 107,
            createdAt: '2026-07-15 12:30:00',
            refundedAt: '2026-08-10 09:00:00',
        );

        $collectionReport = app(KitchenProductionReportService::class)->build(
            Carbon::parse('2026-07-01'),
            Carbon::parse('2026-07-31'),
        );
        $refundReport = app(KitchenProductionReportService::class)->build(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31'),
        );

        self::assertSame(107.0, $collectionReport['summary']['collected_revenue']);
        self::assertSame(0.0, $collectionReport['summary']['refunded_revenue']);
        self::assertSame(107.0, $collectionReport['summary']['net_revenue']);
        self::assertSame(0.0, $refundReport['summary']['collected_revenue']);
        self::assertSame(107.0, $refundReport['summary']['refunded_revenue']);
        self::assertSame(-107.0, $refundReport['summary']['net_revenue']);
        self::assertSame(-107.0, $refundReport['rows']->sole()['net_revenue']);
    }

    private function category(): MenuCategory
    {
        return MenuCategory::create([
            'name' => 'Production Report Financials',
            'slug' => 'production-report-financials-'.str()->lower(str()->random(8)),
        ]);
    }

    private function trackedMenuItem(MenuCategory $category, string $name, float $price): MenuItem
    {
        return MenuItem::create([
            'menu_category_id' => $category->getKey(),
            'name' => $name,
            'slug' => str()->slug($name).'-'.str()->lower(str()->random(8)),
            'price' => $price,
            'tracks_kitchen_production' => true,
            'production_unit' => 'portion',
            'production_usage_per_sale' => 1,
            'low_stock_threshold' => 5,
        ]);
    }

    private function restaurantOrder(string $createdAt, float $total): RestaurantOrder
    {
        $order = RestaurantOrder::create([
            'order_number' => 'FIN-'.str()->upper(str()->random(10)),
            'ordering_channel' => 'web',
            'subtotal' => 100,
            'discount' => 10,
            'vat' => 12.50,
            'nhil' => 2.50,
            'service_charge' => 2,
            'total' => $total,
            'status' => 'served',
            'payment_status' => 'completed',
            'stock_deducted_at' => $createdAt,
        ]);
        $order->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $order;
    }

    /**
     * @return array<string, int|float|string>
     */
    private function orderItem(MenuItem $item, int $quantity, float $unitPrice): array
    {
        return [
            'menu_item_id' => $item->getKey(),
            'item_name' => $item->name,
            'production_unit' => $item->production_unit,
            'production_usage_per_sale' => 1,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $quantity * $unitPrice,
        ];
    }

    private function payment(
        RestaurantOrder $order,
        float $amount,
        string $createdAt,
        ?string $refundedAt = null,
    ): void {
        $payment = Payment::create([
            'restaurant_order_id' => $order->getKey(),
            'amount' => $amount,
            'method' => 'cash',
            'payment_status' => $refundedAt === null ? 'completed' : 'refunded',
            'transaction_reference' => 'FIN-PAY-'.str()->upper(str()->random(10)),
        ]);
        $payment->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
            'refunded_at' => $refundedAt,
        ])->saveQuietly();
    }
}
