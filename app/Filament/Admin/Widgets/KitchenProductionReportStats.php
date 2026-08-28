<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages\KitchenProductionReport;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides a date-range-aware production summary for the kitchen report.
 */
class KitchenProductionReportStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

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
        $reportPage = new KitchenProductionReport;
        $reportPage->fromDate = filled($this->fromDate)
            ? $this->fromDate
            : now()->startOfMonth()->toDateString();
        $reportPage->untilDate = filled($this->untilDate)
            ? $this->untilDate
            : today()->toDateString();
        $summary = $reportPage->getReportProperty()['summary'];
        $rangeLabel = $this->rangeLabel($reportPage->fromDate, $reportPage->untilDate);

        return [
            Stat::make('Tracked items', number_format((int) $summary['tracked_items']))
                ->description($rangeLabel)
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary'),
            Stat::make('Healthy stock', number_format((int) $summary['healthy_items']))
                ->description('Within target after sales')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Low stock', number_format((int) $summary['low_stock_items']))
                ->description('Needs replenishment')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning'),
            Stat::make('Negative variance', number_format((int) $summary['negative_variance_items']))
                ->description('Sales exceed net production')
                ->icon('heroicon-o-arrow-trending-down')
                ->color('danger'),
            Stat::make('Food sales revenue', 'GHS '.number_format((float) $summary['sales_revenue'], 2))
                ->description($rangeLabel)
                ->icon('heroicon-o-banknotes')
                ->color('success'),
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
