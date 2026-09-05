<?php

namespace App\Services;

use App\Models\KitchenProduction;
use App\Models\MenuItem;
use App\Models\RestaurantOrderItem;
use Carbon\CarbonInterface;

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
            ->map(function (MenuItem $item) use ($from, $openingConsumption, $openingProduction, $until): array {
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
                    'sales_revenue' => (float) $deductedItems->sum('total_price')
                        - (float) $reversedItems->sum('total_price'),
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
                'sales_revenue' => $rows->sum('sales_revenue'),
            ],
        ];
    }
}
