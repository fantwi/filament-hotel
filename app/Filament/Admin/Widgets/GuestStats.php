<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages\GuestReport;
use App\Support\Reporting\ReportPeriod;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides a period-aware guest performance summary for the guest report.
 */
class GuestStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    /**
     * The report period selected on the parent guest report page.
     */
    public string $period = 'monthly';

    public string $startDate = '';

    public string $endDate = '';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    /**
     * Builds the guest metrics for the selected reporting period.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $reportPage = new GuestReport;
        $reportPage->period = $this->validPeriod($this->period);
        $reportPage->startDate = $this->startDate;
        $reportPage->endDate = $this->endDate;
        $report = $reportPage->report();
        $periodLabel = $reportPage->periodLabel();

        return [
            Stat::make('Guest profiles', number_format((int) $report['totalGuests']))
                ->description('All registered guest records')
                ->icon('heroicon-o-users')
                ->color('primary'),
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
            Stat::make('Average guest spend', 'GHS '.number_format((float) $report['averageSpend'], 2))
                ->description('Paid spend per guest in '.$periodLabel)
                ->icon('heroicon-o-banknotes')
                ->color('success'),
        ];
    }

    /**
     * Keeps nested widget state within the periods supported by the report page.
     */
    private function validPeriod(string $period): string
    {
        return array_key_exists($period, ReportPeriod::options())
            ? $period
            : 'monthly';
    }
}
