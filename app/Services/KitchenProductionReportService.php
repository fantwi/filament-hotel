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
        $openingSales = RestaurantOrderItem::query()
            ->join('restaurant_orders', 'restaurant_orders.id', '=', 'restaurant_order_items.restaurant_order_id')
            ->where('restaurant_orders.payment_status', 'completed')
            ->where('restaurant_orders.created_at', '<', $from)
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
                        ->where('payment_status', 'completed')
                        ->whereBetween('created_at', [$from, $until]))
                    ->with(['order:id,payment_status,created_at']),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (MenuItem $item) use ($openingProduction, $openingSales): array {
                $produced = (float) $item->kitchenProductions->sum('quantity_produced');
                $wasted = (float) $item->kitchenProductions->sum('quantity_wasted');
                $items = $item->orderItems;
                $soldUnits = (int) $items->sum('quantity');
                $amountSold = (float) $items->sum(
                    fn ($line): float => $line->quantity * (float) $line->production_usage_per_sale
                );
                $netProduced = $produced - $wasted;
                $periodVariance = $netProduced - $amountSold;
                $openingBalance = (float) $openingProduction->get($item->getKey(), 0)
                    - (float) $openingSales->get($item->getKey(), 0);
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
                    'sales_revenue' => (float) $items->sum('total_price'),
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
