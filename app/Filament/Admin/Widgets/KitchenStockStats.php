<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Ingredient;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KitchenStockStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('view kitchen stock') ?? false;
    }

    protected function getStats(): array
    {
        $active = Ingredient::query()->where('is_active', true);
        $stockValue = Ingredient::query()->get()->sum(fn (Ingredient $ingredient): float => $ingredient->stock_value);

        return [
            Stat::make('Active Ingredients', number_format((clone $active)->count()))->icon('heroicon-o-archive-box')->color('primary'),
            Stat::make('Low Stock', number_format((clone $active)->whereColumn('current_stock', '<=', 'reorder_level')->where('current_stock', '>', 0)->count()))->icon('heroicon-o-exclamation-triangle')->color('warning'),
            Stat::make('Out of Stock', number_format((clone $active)->where('current_stock', '<=', 0)->count()))->icon('heroicon-o-x-circle')->color('danger'),
            Stat::make('Current Stock Value', 'GHS '.number_format($stockValue, 2))->icon('heroicon-o-banknotes')->color('success'),
        ];
    }
}
