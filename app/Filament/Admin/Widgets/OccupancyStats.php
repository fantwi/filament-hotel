<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides a period-aware occupancy summary for the occupancy report.
 */
class OccupancyStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    /** @var array{occupancyRate: float|null, bookedRoomNights: int, roomNightCapacity: int} */
    public array $summary = [
        'occupancyRate' => null,
        'bookedRoomNights' => 0,
        'roomNightCapacity' => 0,
    ];

    public string $periodLabel = 'Monthly';

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
        $hasRoomCapacity = $this->summary['occupancyRate'] !== null;

        return [
            Stat::make(
                'Room occupancy',
                $hasRoomCapacity ? number_format((float) $this->summary['occupancyRate'], 1).'%' : 'N/A',
            )
                ->description($hasRoomCapacity ? $this->periodLabel : 'No room-night capacity in '.$this->periodLabel)
                ->icon('heroicon-o-chart-pie')
                ->color($hasRoomCapacity ? 'primary' : 'gray'),
            Stat::make('Booked room nights', number_format($this->summary['bookedRoomNights']))
                ->description('Active stays in '.$this->periodLabel)
                ->icon('heroicon-o-moon')
                ->color('success'),
            Stat::make('Room-night capacity', number_format($this->summary['roomNightCapacity']))
                ->description('Available inventory across '.$this->periodLabel)
                ->icon('heroicon-o-home-modern')
                ->color('info'),
        ];
    }
}
