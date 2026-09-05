<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages\RestaurantOrderReport;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Compares restaurant performance with the preceding equal-length period.
 */
class RestaurantOrderComparisonStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    /** @var array<string, int> */
    protected int|array|null $columns = [
        'default' => 1,
        'md' => 2,
        'xl' => 3,
    ];

    protected ?string $heading = 'Restaurant performance comparison';

    protected ?string $pollingInterval = null;

    /**
     * @var array{
     *     previousPeriodLabel: string,
     *     orders: array{current: int, previous: int, difference: int, percentageChange: float|null},
     *     items: array{current: int, previous: int, difference: int, percentageChange: float|null},
     *     netRevenue: array{current: float, previous: float, difference: float, percentageChange: float|null}
     * }
     */
    public array $comparison = [
        'previousPeriodLabel' => 'Previous period',
        'orders' => ['current' => 0, 'previous' => 0, 'difference' => 0, 'percentageChange' => null],
        'items' => ['current' => 0, 'previous' => 0, 'difference' => 0, 'percentageChange' => null],
        'netRevenue' => ['current' => 0.0, 'previous' => 0.0, 'difference' => 0.0, 'percentageChange' => null],
    ];

    /**
     * Names the exact baseline used by every comparison card.
     */
    public function getDescription(): ?string
    {
        return 'Selected period compared with '.$this->comparison['previousPeriodLabel'].'.';
    }

    /**
     * Restricts the widget to the same roles as the restaurant report.
     */
    public static function canView(): bool
    {
        return RestaurantOrderReport::canAccess();
    }

    /**
     * Builds comparison cards entirely from report-prepared values.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        return [
            $this->countStat('Orders received change', $this->comparison['orders'], 'heroicon-o-shopping-bag'),
            $this->countStat('Items ordered change', $this->comparison['items'], 'heroicon-o-queue-list'),
            $this->moneyStat('Net revenue change', $this->comparison['netRevenue'], 'heroicon-o-banknotes'),
        ];
    }

    /**
     * Presents a signed count difference with selected and previous values.
     *
     * @param  array{current: int, previous: int, difference: int, percentageChange: float|null}  $metric
     */
    private function countStat(string $label, array $metric, string $icon): Stat
    {
        return $this->decorateStat(
            Stat::make($label, $this->signedNumber($metric['difference']))
                ->description(sprintf(
                    'Selected period %s · Previous period %s · %s',
                    number_format($metric['current']),
                    number_format($metric['previous']),
                    $this->changeDescription($metric['difference'], $metric['percentageChange']),
                )),
            $metric['difference'],
            $icon,
        );
    }

    /**
     * Presents a signed monetary difference with selected and previous values.
     *
     * @param  array{current: float, previous: float, difference: float, percentageChange: float|null}  $metric
     */
    private function moneyStat(string $label, array $metric, string $icon): Stat
    {
        $value = match ($metric['difference'] <=> 0.0) {
            1 => '+GHS '.number_format($metric['difference'], 2),
            -1 => '-GHS '.number_format(abs($metric['difference']), 2),
            default => 'GHS 0.00',
        };

        return $this->decorateStat(
            Stat::make($label, $value)
                ->description(sprintf(
                    'Selected period GHS %s · Previous period GHS %s · %s',
                    number_format($metric['current'], 2),
                    number_format($metric['previous'], 2),
                    $this->changeDescription($metric['difference'], $metric['percentageChange']),
                )),
            $metric['difference'],
            $icon,
        );
    }

    /**
     * Applies consistent direction, colour, and responsive number styling.
     */
    private function decorateStat(Stat $stat, float|int $difference, string $icon): Stat
    {
        $direction = $difference <=> 0;

        return $stat
            ->descriptionIcon(match ($direction) {
                1 => 'heroicon-m-arrow-trending-up',
                -1 => 'heroicon-m-arrow-trending-down',
                default => 'heroicon-m-minus',
            })
            ->icon($icon)
            ->color(match ($direction) {
                1 => 'success',
                -1 => 'danger',
                default => 'gray',
            })
            ->extraAttributes([
                'class' => 'min-w-0 [&_.fi-wi-stats-overview-stat-value]:break-words [&_.fi-wi-stats-overview-stat-value]:tabular-nums',
            ]);
    }

    /**
     * Formats the percentage portion of a comparison description.
     */
    private function changeDescription(float|int $difference, ?float $percentage): string
    {
        return match (true) {
            $percentage === null => 'No previous-period baseline',
            $difference > 0 => 'Up '.number_format(abs($percentage), 1).'%',
            $difference < 0 => 'Down '.number_format(abs($percentage), 1).'%',
            default => 'No change',
        };
    }

    /**
     * Adds an explicit sign to non-zero count differences.
     */
    private function signedNumber(int $difference): string
    {
        return match ($difference <=> 0) {
            1 => '+'.number_format($difference),
            -1 => '-'.number_format(abs($difference)),
            default => '0',
        };
    }
}
