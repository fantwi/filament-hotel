<?php

namespace App\Filament\Admin\Widgets;

use App\Services\KitchenProductionReportService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KitchenProductionStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('view kitchen production reports') ?? false;
    }

    protected function getStats(): array
    {
        $r = app(KitchenProductionReportService::class)->build(today(), today())['summary'];

        return [Stat::make('Tracked Food Items', $r['tracked_items'])->color('primary'), Stat::make('Low-Stock Items', $r['low_stock_items'])->color('warning'), Stat::make('Negative Variances', $r['negative_variance_items'])->color('danger'), Stat::make('Food Revenue Today', 'GHS '.number_format($r['sales_revenue'], 2))->color('success')];
    }
}
