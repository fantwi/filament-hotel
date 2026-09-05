<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\MenuItemStockThresholdHistory;
use App\Models\RestaurantOrderItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

        // Aggregate production in SQL so report memory usage remains constant as
        // the number of recorded batches grows.
        $productionTotals = DB::table('kitchen_productions')
            ->where('production_date', '<=', $until)
            ->select('menu_item_id')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN production_date < ? THEN quantity_produced - quantity_wasted ELSE 0 END), 0) as opening_balance',
                [$from],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN production_date BETWEEN ? AND ? THEN quantity_produced ELSE 0 END), 0) as produced',
                [$from, $until],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN production_date BETWEEN ? AND ? THEN quantity_wasted ELSE 0 END), 0) as wasted',
                [$from, $until],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN production_date BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) as period_event_count',
                [$from, $until],
            )
            ->groupBy('menu_item_id')
            ->get()
            ->keyBy('menu_item_id');

        // Finished-food balances follow stock events rather than payment status,
        // so deducted corporate-credit orders remain reportable. Reversals are
        // subtracted in the period in which inventory was restored.
        $consumptionTotals = DB::table('restaurant_order_items')
            ->join('restaurant_orders', 'restaurant_orders.id', '=', 'restaurant_order_items.restaurant_order_id')
            ->where(function ($query) use ($until): void {
                $query
                    ->where('restaurant_orders.stock_deducted_at', '<=', $until)
                    ->orWhere('restaurant_orders.stock_reversed_at', '<=', $until);
            })
            ->select('restaurant_order_items.menu_item_id')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN restaurant_orders.stock_deducted_at < ? THEN restaurant_order_items.quantity * restaurant_order_items.production_usage_per_sale ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN restaurant_orders.stock_reversed_at < ? THEN restaurant_order_items.quantity * restaurant_order_items.production_usage_per_sale ELSE 0 END), 0) as opening_consumption',
                [$from, $from],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN restaurant_orders.stock_deducted_at BETWEEN ? AND ? THEN restaurant_order_items.quantity ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN restaurant_orders.stock_reversed_at BETWEEN ? AND ? THEN restaurant_order_items.quantity ELSE 0 END), 0) as sold_units',
                [$from, $until, $from, $until],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN restaurant_orders.stock_deducted_at BETWEEN ? AND ? THEN restaurant_order_items.quantity * restaurant_order_items.production_usage_per_sale ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN restaurant_orders.stock_reversed_at BETWEEN ? AND ? THEN restaurant_order_items.quantity * restaurant_order_items.production_usage_per_sale ELSE 0 END), 0) as production_amount_sold',
                [$from, $until, $from, $until],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN restaurant_orders.stock_deducted_at BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) + COALESCE(SUM(CASE WHEN restaurant_orders.stock_reversed_at BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) as period_event_count',
                [$from, $until, $from, $until],
            )
            ->groupBy('restaurant_order_items.menu_item_id')
            ->get()
            ->keyBy('menu_item_id');
        $collectedRevenue = $this->allocatedPaymentAmounts($from, $until, 'created_at', collections: true);
        $refundedRevenue = $this->allocatedPaymentAmounts($from, $until, 'refunded_at', collections: false);
        $hasPeriodActivity = false;

        $thresholdAtPeriodEnd = MenuItemStockThresholdHistory::query()
            ->select('threshold')
            ->whereColumn(
                'menu_item_stock_threshold_histories.menu_item_id',
                'menu_items.id',
            )
            ->where('effective_from', '<=', $until)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->limit(1);

        $rows = MenuItem::query()
            ->leftJoin('menu_categories', 'menu_categories.id', '=', 'menu_items.menu_category_id')
            ->where('menu_items.tracks_kitchen_production', true)
            ->select([
                'menu_items.id',
                'menu_items.name',
                'menu_items.production_unit',
                'menu_items.production_usage_per_sale',
                'menu_items.low_stock_threshold',
                'menu_categories.name as category_name',
            ])
            ->addSelect(['report_low_stock_threshold' => $thresholdAtPeriodEnd])
            ->orderBy('menu_items.name')
            ->get()
            ->map(function (MenuItem $item) use ($collectedRevenue, $consumptionTotals, &$hasPeriodActivity, $productionTotals, $refundedRevenue): array {
                $production = $productionTotals->get($item->getKey());
                $consumption = $consumptionTotals->get($item->getKey());
                $produced = (float) ($production->produced ?? 0);
                $wasted = (float) ($production->wasted ?? 0);
                $soldUnits = (int) ($consumption->sold_units ?? 0);
                $amountSold = (float) ($consumption->production_amount_sold ?? 0);
                $netProduced = $produced - $wasted;
                $periodVariance = $netProduced - $amountSold;
                $openingBalance = (float) ($production->opening_balance ?? 0)
                    - (float) ($consumption->opening_consumption ?? 0);
                $availableStock = $openingBalance + $netProduced;
                $closingBalance = $openingBalance + $periodVariance;
                $sellThrough = $availableStock > 0 && $amountSold >= 0
                    ? ($amountSold / $availableStock) * 100
                    : null;
                $itemCollectedRevenue = round((float) $collectedRevenue->get($item->getKey(), 0), 2);
                $itemRefundedRevenue = round((float) $refundedRevenue->get($item->getKey(), 0), 2);
                $lowStockThreshold = (float) ($item->report_low_stock_threshold ?? $item->low_stock_threshold);
                $hasPeriodActivity = $hasPeriodActivity
                    || (int) ($production->period_event_count ?? 0) > 0
                    || (int) ($consumption->period_event_count ?? 0) > 0
                    || abs($itemCollectedRevenue) > 0.00001
                    || abs($itemRefundedRevenue) > 0.00001;
                $stockStatus = $closingBalance < 0
                    ? 'negative'
                    : ($closingBalance <= $lowStockThreshold ? 'low' : 'healthy');

                return [
                    'menu_item_id' => $item->getKey(),
                    'name' => $item->name,
                    'category' => $item->category_name ?? 'Uncategorised',
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
                    'low_stock_threshold' => $lowStockThreshold,
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
            'has_period_activity' => $hasPeriodActivity,
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
