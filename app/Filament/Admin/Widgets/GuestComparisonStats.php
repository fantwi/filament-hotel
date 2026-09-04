<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Compares selected-period guest activity with the preceding equal-length period.
 */
class GuestComparisonStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    /** @var array<string, int> */
    protected int|array|null $columns = [
        'default' => 1,
        'md' => 2,
        'xl' => 3,
    ];

    protected ?string $heading = 'Previous-period comparison';

    /**
     * @var array{
     *     previousPeriodLabel: string,
     *     newGuests: array{current: int, previous: int, difference: int, percentageChange: float|null},
     *     payingGuests: array{current: int, previous: int, difference: int, percentageChange: float|null},
     *     returningGuests: array{current: int, previous: int, difference: int, percentageChange: float|null}
     * }
     */
    public array $comparison = [
        'previousPeriodLabel' => 'Previous period',
        'newGuests' => ['current' => 0, 'previous' => 0, 'difference' => 0, 'percentageChange' => null],
        'payingGuests' => ['current' => 0, 'previous' => 0, 'difference' => 0, 'percentageChange' => null],
        'returningGuests' => ['current' => 0, 'previous' => 0, 'difference' => 0, 'percentageChange' => null],
    ];

    /**
     * Names the exact baseline represented by the comparison cards.
     */
    public function getDescription(): ?string
    {
        return 'Changes against '.$this->comparison['previousPeriodLabel'].'.';
    }

    /**
     * Restricts the comparison to the same roles as the guest report.
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    /**
     * Builds comparison cards entirely from report-prepared values.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        return [
            $this->makeStat('New guest change', $this->comparison['newGuests'], 'heroicon-o-user-plus'),
            $this->makeStat('Paying guest change', $this->comparison['payingGuests'], 'heroicon-o-credit-card'),
            $this->makeStat('Returning guest change', $this->comparison['returningGuests'], 'heroicon-o-arrow-path-rounded-square'),
        ];
    }

    /**
     * Presents a signed count change with its current and previous values.
     *
     * @param  array{current: int, previous: int, difference: int, percentageChange: float|null}  $metric
     */
    private function makeStat(string $label, array $metric, string $icon): Stat
    {
        $direction = $metric['difference'] <=> 0;
        $percentage = match (true) {
            $metric['percentageChange'] === null => 'No previous-period baseline',
            $direction > 0 => 'Up '.number_format(abs($metric['percentageChange']), 1).'%',
            $direction < 0 => 'Down '.number_format(abs($metric['percentageChange']), 1).'%',
            default => 'No change',
        };

        return Stat::make($label, $this->formatDifference($metric['difference']))
            ->description(sprintf(
                'Current %s · Previous %s · %s',
                number_format($metric['current']),
                number_format($metric['previous']),
                $percentage,
            ))
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
     * Adds an explicit sign to non-zero guest-count differences.
     */
    private function formatDifference(int $difference): string
    {
        return match ($difference <=> 0) {
            1 => '+'.number_format($difference),
            -1 => '-'.number_format(abs($difference)),
            default => '0',
        };
    }
}
