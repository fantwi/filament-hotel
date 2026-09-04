<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages\OccupancyReport;
use App\Support\Reporting\ReportPeriod;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides a period-aware occupancy summary for the occupancy report.
 */
class OccupancyStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    /**
     * The report period selected on the parent occupancy page.
     */
    public string $period = 'monthly';

    public string $startDate = '';

    public string $endDate = '';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'receptionist']) ?? false;
    }

    /**
     * Builds the occupancy metrics for the selected reporting period.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $reportPage = new OccupancyReport;
        $reportPage->period = $this->validPeriod($this->period);
        $reportPage->startDate = $this->startDate;
        $reportPage->endDate = $this->endDate;
        $report = $reportPage->report();
        $periodLabel = $reportPage->periodLabel();
        $hasRoomCapacity = $report['occupancyRate'] !== null;

        return [
            Stat::make(
                'Room occupancy',
                $hasRoomCapacity ? number_format((float) $report['occupancyRate'], 1).'%' : 'N/A',
            )
                ->description($hasRoomCapacity ? $periodLabel : 'No room-night capacity in '.$periodLabel)
                ->icon('heroicon-o-chart-pie')
                ->color($hasRoomCapacity ? 'primary' : 'gray'),
            Stat::make('Booked room nights', number_format((int) $report['bookedRoomNights']))
                ->description('Active stays in '.$periodLabel)
                ->icon('heroicon-o-moon')
                ->color('success'),
            Stat::make('Room-night capacity', number_format((int) $report['roomNightCapacity']))
                ->description('Available inventory across '.$periodLabel)
                ->icon('heroicon-o-home-modern')
                ->color('info'),
            Stat::make('Rooms available now', number_format((int) $report['roomStatus']['available']))
                ->description('Live inventory status')
                ->icon('heroicon-o-key')
                ->color('primary'),
            Stat::make('Tables available now', number_format((int) $report['tableStatus']['available']))
                ->description('Live restaurant status')
                ->icon('heroicon-o-square-3-stack-3d')
                ->color('warning'),
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
