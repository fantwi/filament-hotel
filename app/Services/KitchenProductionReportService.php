<?php

namespace App\Services;

use App\Models\MenuItem;
use Carbon\CarbonInterface;

class KitchenProductionReportService
{
    public function build(CarbonInterface $from, CarbonInterface $until): array
    {
        $from = $from->copy()->startOfDay();
        $until = $until->copy()->endOfDay();

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
            ->map(function (MenuItem $item): array {
                $produced = (float) $item->kitchenProductions->sum('quantity_produced');
                $wasted = (float) $item->kitchenProductions->sum('quantity_wasted');
                $items = $item->orderItems;
                $soldUnits = (int) $items->sum('quantity');
                $amountSold = (float) $items->sum(
                    fn ($line): float => $line->quantity * (float) $line->production_usage_per_sale
                );
                $netProduced = $produced - $wasted;
                $remaining = $netProduced - $amountSold;
                $sellThrough = $netProduced > 0 ? ($amountSold / $netProduced) * 100 : 0;
                $status = $remaining < 0
                    ? 'negative'
                    : ($remaining <= (float) $item->low_stock_threshold ? 'low' : 'healthy');

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
                    'remaining' => $remaining,
                    'sell_through' => $sellThrough,
                    'sales_revenue' => (float) $items->sum('total_price'),
                    'status' => $status,
                ];
            });

        return [
            'rows' => $rows,
            'summary' => [
                'tracked_items' => $rows->count(),
                'healthy_items' => $rows->where('status', 'healthy')->count(),
                'low_stock_items' => $rows->where('status', 'low')->count(),
                'negative_variance_items' => $rows->where('status', 'negative')->count(),
                'sales_revenue' => $rows->sum('sales_revenue'),
            ],
        ];
    }
}
