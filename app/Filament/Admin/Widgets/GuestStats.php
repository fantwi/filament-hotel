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

    /** @var array<string, string|null> */
    public array $drillDownUrls = [];

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
            $this->withDrillDown(
                Stat::make('New guest profiles', number_format((int) $report['newGuests']))
                    ->description('Profiles created · '.$periodLabel)
                    ->icon('heroicon-o-user-plus')
                    ->color('success'),
                'newGuests',
            ),
            $this->withDrillDown(
                Stat::make('Guests with collected payments', number_format((int) $report['payingGuests']))
                    ->description('Distinct guests linked to collected payments')
                    ->icon('heroicon-o-credit-card')
                    ->color('info'),
                'payingGuests',
            ),
            $this->withDrillDown(
                Stat::make('Repeat-service guests', number_format((int) $report['returningGuests']))
                    ->description('Two or more paid service visits')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->color('warning'),
                'returningGuests',
            ),
            $this->withDrillDown(
                Stat::make('Average collected per paying guest', 'GHS '.number_format((float) $report['averageSpend'], 2))
                    ->description('Gross guest revenue per paying guest · '.$periodLabel)
                    ->icon('heroicon-o-banknotes')
                    ->color('success'),
                'averageSpend',
            ),
        ];
    }

    /**
     * Makes a metric visibly actionable only when an authorized URL is supplied.
     */
    private function withDrillDown(Stat $stat, string $key): Stat
    {
        $url = $this->drillDownUrls[$key] ?? null;

        return filled($url)
            ? $stat->url($url)->descriptionIcon('heroicon-m-arrow-top-right-on-square')
            : $stat;
    }
}
