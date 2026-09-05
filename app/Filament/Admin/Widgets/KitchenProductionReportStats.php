<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides a date-range-aware production summary for the kitchen report.
 */
class KitchenProductionReportStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    /**
     * Summary calculated once by the parent kitchen report page.
     *
     * @var array{tracked_items: int, healthy_items: int, low_stock_items: int, negative_variance_items: int, net_revenue: float}
     */
    public array $summary = [
        'tracked_items' => 0,
        'healthy_items' => 0,
        'low_stock_items' => 0,
        'negative_variance_items' => 0,
        'net_revenue' => 0.0,
    ];

    /**
     * Start date selected on the parent kitchen report page.
     */
    public string $fromDate = '';

    /**
     * End date selected on the parent kitchen report page.
     */
    public string $untilDate = '';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view kitchen production reports') ?? false;
    }

    /**
     * Builds production and sales metrics for the selected date range.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $summary = $this->summary;
        $fromDate = filled($this->fromDate)
            ? $this->fromDate
            : now()->startOfMonth()->toDateString();
        $untilDate = filled($this->untilDate)
            ? $this->untilDate
            : today()->toDateString();
        $rangeLabel = $this->rangeLabel($fromDate, $untilDate);

        return [
            Stat::make('Tracked items', number_format((int) $summary['tracked_items']))
                ->description($rangeLabel)
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary'),
            Stat::make('Healthy finished-food balances', number_format((int) $summary['healthy_items']))
                ->description('Above threshold at period end')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Low finished-food balances', number_format((int) $summary['low_stock_items']))
                ->description('At or below threshold at period end')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning'),
            Stat::make('Negative variance', number_format((int) $summary['negative_variance_items']))
                ->description('Period sales exceed net production')
                ->icon('heroicon-o-arrow-trending-down')
                ->color('danger'),
            Stat::make('Tracked-item net revenue', 'GHS '.number_format((float) $summary['net_revenue'], 2))
                ->description('Allocated collections less refunds · '.$rangeLabel)
                ->icon('heroicon-o-banknotes')
                ->color((float) $summary['net_revenue'] < 0 ? 'danger' : 'success'),
        ];
    }

    /**
     * Formats the selected production date range for stat descriptions.
     */
    private function rangeLabel(string $fromDate, string $untilDate): string
    {
        return $fromDate === $untilDate
            ? date('M j, Y', strtotime($fromDate))
            : date('M j, Y', strtotime($fromDate)).' - '.date('M j, Y', strtotime($untilDate));
    }
}
