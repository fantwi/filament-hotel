<?php

namespace App\Services;

use App\Models\MenuItem;
use Carbon\CarbonInterface;

class KitchenProductionReportService
{
    public function build(CarbonInterface $from, CarbonInterface $until): array
    {
        $rows = MenuItem::query()->where('tracks_kitchen_production', true)->with(['kitchenProductions' => fn ($q) => $q->whereBetween('production_date', [$from->toDateString(), $until->toDateString()]), 'orderItems.order'])->get()->map(function (MenuItem $item) use ($from, $until): array {
            $produced = (float) $item->kitchenProductions->sum('quantity_produced'); $wasted = (float) $item->kitchenProductions->sum('quantity_wasted');
            $items = $item->orderItems->filter(fn ($line) => $line->order && $line->order->payment_status === 'completed' && $line->order->created_at->between($from->copy()->startOfDay(), $until->copy()->endOfDay()));
            $soldUnits = (int) $items->sum('quantity'); $amountSold = (float) $items->sum(fn ($line) => $line->quantity * $line->production_usage_per_sale);
            $net = $produced - $wasted; $remaining = $net - $amountSold; $sellThrough = $net > 0 ? $amountSold / $net * 100 : 0;
            $status = $remaining < 0 ? 'negative' : ($remaining <= (float) $item->low_stock_threshold ? 'low' : 'healthy');
            return ['name' => $item->name, 'unit' => $item->production_unit, 'produced' => $produced, 'wasted' => $wasted, 'net_produced' => $net, 'sold_units' => $soldUnits, 'production_amount_sold' => $amountSold, 'remaining' => $remaining, 'sell_through' => $sellThrough, 'sales_revenue' => (float) $items->sum('total_price'), 'status' => $status];
        });
        return ['rows' => $rows, 'summary' => ['tracked_items' => $rows->count(), 'low_stock_items' => $rows->where('status', 'low')->count(), 'negative_variance_items' => $rows->where('status', 'negative')->count(), 'sales_revenue' => $rows->sum('sales_revenue')]];
    }
}
