<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\KitchenStockMovement;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/**
 * Provides the kitchen stock stats Filament dashboard widget.
 */
class KitchenStockStats extends StatsOverviewWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view kitchen stock') ?? false;
    }

    /**
     * Builds and returns stats.
     */
    protected function getStats(): array
    {
        $movements = $this->forDashboardDateRange(
            KitchenStockMovement::query(),
            'kitchen_stock_movements.occurred_at',
        );
        $inbound = $this->movementSummary(
            (clone $movements)->where('kitchen_stock_movements.direction', KitchenStockMovement::DIRECTION_IN),
        );
        $consumption = $this->movementSummary(
            (clone $movements)->where('kitchen_stock_movements.type', KitchenStockMovement::TYPE_CONSUMPTION),
        );
        $wastage = $this->movementSummary(
            (clone $movements)->where('kitchen_stock_movements.type', KitchenStockMovement::TYPE_WASTAGE),
        );

        return [
            Stat::make(
                'Ingredients Moved',
                number_format((clone $movements)
                    ->distinct('kitchen_stock_movements.ingredient_id')
                    ->count('kitchen_stock_movements.ingredient_id')),
            )->description($this->dashboardDateRangeLabel())->icon('heroicon-o-archive-box')->color('primary'),
            Stat::make('Inbound Movements', number_format($inbound['count']))
                ->description($inbound['description'])
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
            Stat::make('Consumption Movements', number_format($consumption['count']))
                ->description($consumption['description'])
                ->icon('heroicon-o-fire')
                ->color('warning'),
            Stat::make('Wastage Movements', number_format($wastage['count']))
                ->description($wastage['description'])
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }

    /**
     * Summarize movement activity without combining incompatible stock units.
     *
     * @return array{count: int, description: string}
     */
    private function movementSummary(Builder $query): array
    {
        $groups = $query
            ->leftJoin('ingredients', 'ingredients.id', '=', 'kitchen_stock_movements.ingredient_id')
            ->selectRaw("COALESCE(NULLIF(ingredients.unit, ''), 'unspecified') as stock_unit")
            ->selectRaw('COUNT(*) as movement_count')
            ->selectRaw('SUM(kitchen_stock_movements.quantity) as stock_quantity')
            ->groupBy('ingredients.unit')
            ->orderBy('ingredients.unit')
            ->get();

        $count = (int) $groups->sum('movement_count');

        if ($count === 0) {
            return [
                'count' => 0,
                'description' => 'No movements in selected period',
            ];
        }

        $quantities = $groups
            ->map(fn (KitchenStockMovement $group): string => sprintf(
                '%s %s',
                $this->formatQuantity((float) $group->stock_quantity),
                $group->stock_unit,
            ))
            ->implode(' · ');

        return [
            'count' => $count,
            'description' => sprintf(
                '%s across %s %s',
                $quantities,
                number_format($count),
                str('movement')->plural($count),
            ),
        ];
    }

    /**
     * Format a decimal quantity without insignificant trailing zeroes.
     */
    private function formatQuantity(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.');
    }
}
