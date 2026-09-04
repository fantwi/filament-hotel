<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Charts room-night utilisation from the occupancy report's prepared data.
 */
class OccupancyTrendChart extends ChartWidget
{
    protected ?string $heading = 'Occupancy trend';

    protected ?string $emptyStateHeading = 'No room inventory for this period';

    protected ?string $emptyStateDescription = 'Add rooms or select a period with room-night capacity to view the occupancy trend.';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    /**
     * @var array{
     *     granularity: string,
     *     labels: list<string>,
     *     bookedRoomNights: list<int>,
     *     roomNightCapacities: list<int>,
     *     occupancyRates: list<float|null>
     * }
     */
    public array $trend = [
        'granularity' => 'day',
        'labels' => [],
        'bookedRoomNights' => [],
        'roomNightCapacities' => [],
        'occupancyRates' => [],
    ];

    public string $periodLabel = 'Monthly';

    /**
     * Explains the date scope and automatically selected chart resolution.
     */
    public function getDescription(): string|Htmlable|null
    {
        return sprintf(
            '%s room utilisation grouped by %s.',
            $this->periodLabel,
            $this->trend['granularity'],
        );
    }

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'receptionist']) ?? false;
    }

    /**
     * Uses capacity—not bookings—to distinguish an empty hotel from zero occupancy.
     */
    public function isEmpty(): bool
    {
        return collect($this->trend['roomNightCapacities'])
            ->every(fn (int $capacity): bool => $capacity === 0);
    }

    /**
     * Builds the mixed percentage and room-night datasets without database work.
     */
    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Occupancy rate',
                    'data' => $this->trend['occupancyRates'],
                    'type' => 'line',
                    'yAxisID' => 'yPercentage',
                    'borderColor' => '#4F46E5',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.18)',
                    'pointBackgroundColor' => '#4338CA',
                    'pointBorderColor' => '#FFFFFF',
                    'pointRadius' => 3,
                    'borderWidth' => 3,
                    'tension' => 0.3,
                    'spanGaps' => false,
                ],
                [
                    'label' => 'Booked room nights',
                    'data' => $this->trend['bookedRoomNights'],
                    'yAxisID' => 'yNights',
                    'borderColor' => '#059669',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.72)',
                    'borderWidth' => 1,
                    'borderRadius' => 5,
                ],
                [
                    'label' => 'Room-night capacity',
                    'data' => $this->trend['roomNightCapacities'],
                    'yAxisID' => 'yNights',
                    'borderColor' => '#0284C7',
                    'backgroundColor' => 'rgba(14, 165, 233, 0.38)',
                    'borderWidth' => 1,
                    'borderRadius' => 5,
                ],
            ],
            'labels' => $this->trend['labels'],
        ];
    }

    /**
     * Renders room-night totals as bars with occupancy overlaid as a line.
     */
    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Keeps the mixed scales and legend readable across screen sizes.
     */
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'boxWidth' => 8,
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'ticks' => [
                        'autoSkip' => true,
                        'maxTicksLimit' => 12,
                        'maxRotation' => 0,
                    ],
                ],
                'yNights' => [
                    'beginAtZero' => true,
                    'position' => 'left',
                    'title' => ['display' => true, 'text' => 'Room nights'],
                    'ticks' => ['precision' => 0],
                ],
                'yPercentage' => [
                    'beginAtZero' => true,
                    'suggestedMax' => 100,
                    'position' => 'right',
                    'grid' => ['drawOnChartArea' => false],
                    'title' => ['display' => true, 'text' => 'Occupancy (%)'],
                ],
            ],
        ];
    }
}
