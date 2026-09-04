<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Compares the selected revenue period with the preceding equal-length period.
 */
class RevenueComparisonStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    /** @var array<string, int> */
    protected int|array|null $columns = [
        'default' => 1,
        'md' => 2,
        'xl' => 3,
    ];

    protected ?string $heading = 'Previous-period comparison';

    protected ?string $description = 'Changes against the immediately preceding date range of equal length.';

    /**
     * @var array{
     *     previousPeriodLabel: string,
     *     revenue: array{current: float, previous: float, difference: float, percentageChange: float|null},
     *     refunds: array{current: float, previous: float, difference: float, percentageChange: float|null},
     *     netRevenue: array{current: float, previous: float, difference: float, percentageChange: float|null}
     * }
     */
    public array $comparison = [
        'previousPeriodLabel' => 'Previous period',
        'revenue' => ['current' => 0.0, 'previous' => 0.0, 'difference' => 0.0, 'percentageChange' => null],
        'refunds' => ['current' => 0.0, 'previous' => 0.0, 'difference' => 0.0, 'percentageChange' => null],
        'netRevenue' => ['current' => 0.0, 'previous' => 0.0, 'difference' => 0.0, 'percentageChange' => null],
    ];

    /** @var array<string, string> */
    public array $drillDownUrls = [];

    /**
     * Restricts the comparison to the same roles as the revenue report.
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    /**
     * Builds the comparison cards entirely from report-prepared values.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        return [
            $this->makeStat('Collected revenue change', 'revenue', $this->comparison['revenue'], true, 'heroicon-o-banknotes'),
            $this->makeStat('Refund change', 'refunds', $this->comparison['refunds'], false, 'heroicon-o-arrow-uturn-left'),
            $this->makeStat('Net revenue change', 'netRevenue', $this->comparison['netRevenue'], true, 'heroicon-o-chart-bar-square'),
        ];
    }

    /**
     * Presents a signed monetary difference and enough context to verify it.
     *
     * @param  array{current: float, previous: float, difference: float, percentageChange: float|null}  $metric
     */
    private function makeStat(string $label, string $urlKey, array $metric, bool $increaseIsPositive, string $icon): Stat
    {
        $direction = $metric['difference'] <=> 0.0;
        $percentage = match (true) {
            $metric['percentageChange'] === null => 'No previous-period baseline',
            $direction > 0 => 'Up '.number_format(abs($metric['percentageChange']), 1).'%',
            $direction < 0 => 'Down '.number_format(abs($metric['percentageChange']), 1).'%',
            default => 'No change',
        };

        $stat = Stat::make($label, $this->formatDifference($metric['difference']))
            ->description(sprintf(
                'Current GHS %s · Previous GHS %s · %s',
                number_format($metric['current'], 2),
                number_format($metric['previous'], 2),
                $percentage,
            ))
            ->descriptionIcon(match ($direction) {
                1 => 'heroicon-m-arrow-trending-up',
                -1 => 'heroicon-m-arrow-trending-down',
                default => 'heroicon-m-minus',
            })
            ->icon($icon)
            ->color($this->metricColor($direction, $increaseIsPositive))
            ->extraAttributes([
                'class' => 'min-w-0 [&_.fi-wi-stats-overview-stat-value]:break-words [&_.fi-wi-stats-overview-stat-value]:tabular-nums',
            ]);

        $url = $this->drillDownUrls[$urlKey] ?? null;

        return filled($url)
            ? $stat->url($url)->descriptionIcon('heroicon-m-arrow-top-right-on-square')
            : $stat;
    }

    /**
     * Adds an explicit sign while retaining a conventional currency format.
     */
    private function formatDifference(float $difference): string
    {
        return match ($difference <=> 0.0) {
            1 => '+GHS '.number_format($difference, 2),
            -1 => '-GHS '.number_format(abs($difference), 2),
            default => 'GHS 0.00',
        };
    }

    /**
     * Treats increased collections as favorable and increased refunds as unfavorable.
     */
    private function metricColor(int $direction, bool $increaseIsPositive): string
    {
        if ($direction === 0) {
            return 'gray';
        }

        return ($direction > 0) === $increaseIsPositive ? 'success' : 'danger';
    }
}
