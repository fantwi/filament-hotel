<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Presents the guest performance summary calculated by the parent report page.
 */
class GuestStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    /** @var array<string, int> */
    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 2,
        'xl' => 4,
    ];

    /** @var array{newGuests: int, payingGuests: int, returningGuests: int, averageSpend: float} */
    public array $reportData = [
        'newGuests' => 0,
        'payingGuests' => 0,
        'returningGuests' => 0,
        'averageSpend' => 0.0,
    ];

    public string $reportPeriodLabel = 'Monthly';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    /**
     * Builds guest metric cards from the parent page's precomputed data.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $report = $this->reportData;
        $periodLabel = $this->reportPeriodLabel;

        return [
            Stat::make('New guests', number_format((int) $report['newGuests']))
                ->description('Profiles created in '.$periodLabel)
                ->icon('heroicon-o-user-plus')
                ->color('success'),
            Stat::make('Paying guests', number_format((int) $report['payingGuests']))
                ->description('Guests with paid activity')
                ->icon('heroicon-o-credit-card')
                ->color('info'),
            Stat::make('Returning guests', number_format((int) $report['returningGuests']))
                ->description('Two or more paid service visits')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('warning'),
            Stat::make('Average gross spend', 'GHS '.number_format((float) $report['averageSpend'], 2))
                ->description('Gross collections per paying guest in '.$periodLabel)
                ->icon('heroicon-o-banknotes')
                ->color('success'),
        ];
    }
}
