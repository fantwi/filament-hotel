<?php

namespace App\Services;

use App\Models\KitchenProduction;
use App\Models\MenuItem;
use App\Models\RestaurantOrderItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Encapsulates business rules for kitchen production report service.
 */
class KitchenProductionReportService
{
    /**
     * Builds .
     */
    public function build(CarbonInterface $from, CarbonInterface $until): array
    {
        $from = $from->copy()->startOfDay();
        $until = $until->copy()->endOfDay();

        $openingProduction = KitchenProduction::query()
            ->where('production_date', '<', $from->toDateString())
            ->selectRaw('menu_item_id, SUM(quantity_produced - quantity_wasted) as balance')
            ->groupBy('menu_item_id')
            ->pluck('balance', 'menu_item_id');
        // Finished-food balances follow inventory events rather than payment
        // status, so deducted corporate-credit orders remain reportable.
        $openingConsumption = RestaurantOrderItem::query()
            ->join('restaurant_orders', 'restaurant_orders.id', '=', 'restaurant_order_items.restaurant_order_id')
            ->whereNotNull('restaurant_orders.stock_deducted_at')
            ->where('restaurant_orders.stock_deducted_at', '<', $from)
            ->where(function ($query) use ($from): void {
                $query
                    ->whereNull('restaurant_orders.stock_reversed_at')
                    ->orWhere('restaurant_orders.stock_reversed_at', '>=', $from);
            })
            ->selectRaw('restaurant_order_items.menu_item_id, SUM(restaurant_order_items.quantity * restaurant_order_items.production_usage_per_sale) as balance')
            ->groupBy('restaurant_order_items.menu_item_id')
            ->pluck('balance', 'restaurant_order_items.menu_item_id');
        $collectedRevenue = $this->allocatedPaymentAmounts($from, $until, 'created_at', collections: true);
        $refundedRevenue = $this->allocatedPaymentAmounts($from, $until, 'refunded_at', collections: false);

        $rows = MenuItem::query()
            ->where('tracks_kitchen_production', true)
            ->with([
                'category:id,name',
                'kitchenProductions' => fn ($query) => $query
                    ->whereBetween('production_date', [$from->toDateString(), $until->toDateString()]),
                'orderItems' => fn ($query) => $query
                    ->whereHas('order', fn ($query) => $query
                        ->where(function ($eventQuery) use ($from, $until): void {
                            $eventQuery
                                ->whereBetween('stock_deducted_at', [$from, $until])
                                ->orWhereBetween('stock_reversed_at', [$from, $until]);
                        }))
                    ->with(['order:id,stock_deducted_at,stock_reversed_at']),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (MenuItem $item) use ($collectedRevenue, $from, $openingConsumption, $openingProduction, $refundedRevenue, $until): array {
                $produced = (float) $item->kitchenProductions->sum('quantity_produced');
                $wasted = (float) $item->kitchenProductions->sum('quantity_wasted');
                $items = $item->orderItems;
                $deductedItems = $items->filter(
                    fn ($line): bool => $line->order?->stock_deducted_at?->betweenIncluded($from, $until) ?? false,
                );
                $reversedItems = $items->filter(
                    fn ($line): bool => $line->order?->stock_reversed_at?->betweenIncluded($from, $until) ?? false,
                );
                $soldUnits = (int) $deductedItems->sum('quantity') - (int) $reversedItems->sum('quantity');
                $amountDeducted = (float) $deductedItems->sum(
                    fn ($line): float => $line->quantity * (float) $line->production_usage_per_sale
                );
                $amountReversed = (float) $reversedItems->sum(
                    fn ($line): float => $line->quantity * (float) $line->production_usage_per_sale
                );
                $amountSold = $amountDeducted - $amountReversed;
                $netProduced = $produced - $wasted;
                $periodVariance = $netProduced - $amountSold;
                $openingBalance = (float) $openingProduction->get($item->getKey(), 0)
                    - (float) $openingConsumption->get($item->getKey(), 0);
                $closingBalance = $openingBalance + $periodVariance;
                $sellThrough = $netProduced > 0 ? ($amountSold / $netProduced) * 100 : 0;
                $itemCollectedRevenue = round((float) $collectedRevenue->get($item->getKey(), 0), 2);
                $itemRefundedRevenue = round((float) $refundedRevenue->get($item->getKey(), 0), 2);
                $stockStatus = $closingBalance < 0
                    ? 'negative'
                    : ($closingBalance <= (float) $item->low_stock_threshold ? 'low' : 'healthy');

                return [
                    'name' => $item->name,
                    'category' => $item->category?->name ?? 'Uncategorised',
                    'unit' => $item->production_unit,
                    'usage_per_sale' => (float) $item->production_usage_per_sale,
                    'produced' => $produced,
                    'wasted' => $wasted,
                    'net_produced' => $netProduced,
                    'sold_units' => $soldUnits,
                    'production_amount_sold' => $amountSold,
                    'opening_balance' => $openingBalance,
                    'period_variance' => $periodVariance,
                    'closing_balance' => $closingBalance,
                    'remaining' => $periodVariance,
                    'sell_through' => $sellThrough,
                    'collected_revenue' => $itemCollectedRevenue,
                    'refunded_revenue' => $itemRefundedRevenue,
                    'net_revenue' => round($itemCollectedRevenue - $itemRefundedRevenue, 2),
                    'stock_status' => $stockStatus,
                    'status' => $stockStatus,
                ];
            });

        return [
            'rows' => $rows,
            'summary' => [
                'tracked_items' => $rows->count(),
                'healthy_items' => $rows->where('stock_status', 'healthy')->count(),
                'low_stock_items' => $rows->whereIn('stock_status', ['low', 'negative'])->count(),
                'negative_variance_items' => $rows->filter(
                    fn (array $row): bool => $row['period_variance'] < 0,
                )->count(),
                'collected_revenue' => round((float) $rows->sum('collected_revenue'), 2),
                'refunded_revenue' => round((float) $rows->sum('refunded_revenue'), 2),
                'net_revenue' => round((float) $rows->sum('net_revenue'), 2),
            ],
        ];
    }

    /**
     * Allocates order-level collections or refunds across their menu-item
     * lines, preserving discounts and charges recorded in the payment ledger.
     *
     * @return Collection<int, float|string>
     */
    private function allocatedPaymentAmounts(
        CarbonInterface $from,
        CarbonInterface $until,
        string $eventColumn,
        bool $collections,
    ): Collection {
        $orderLineTotals = RestaurantOrderItem::query()
            ->select('restaurant_order_id')
            ->selectRaw('SUM(total_price) as order_line_total')
            ->groupBy('restaurant_order_id');

        $query = RestaurantOrderItem::query()
            ->joinSub($orderLineTotals, 'order_item_totals', function ($join): void {
                $join->on(
                    'order_item_totals.restaurant_order_id',
                    '=',
                    'restaurant_order_items.restaurant_order_id',
                );
            })
            ->join('payments', 'payments.restaurant_order_id', '=', 'restaurant_order_items.restaurant_order_id')
            ->where('order_item_totals.order_line_total', '>', 0)
            ->whereBetween("payments.{$eventColumn}", [$from, $until]);

        if ($collections) {
            $query->whereIn('payments.payment_status', ['paid', 'completed', 'refunded', 'refund']);
        } else {
            $query->whereNotNull('payments.refunded_at');
        }

        return $query
            ->selectRaw(
                'restaurant_order_items.menu_item_id, COALESCE(SUM((payments.amount * restaurant_order_items.total_price) / NULLIF(order_item_totals.order_line_total, 0)), 0) as allocated_amount',
            )
            ->groupBy('restaurant_order_items.menu_item_id')
            ->pluck('allocated_amount', 'restaurant_order_items.menu_item_id');
    }
}
