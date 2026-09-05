<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Charts new, paying, and returning guests from prepared report data.
 */
class GuestTrendChart extends ChartWidget
{
    protected ?string $heading = 'Guest activity over time';

    protected ?string $emptyStateHeading = 'No guest activity in this period';

    protected ?string $emptyStateDescription = 'Choose another reporting period to view new guest profiles, collected payments, and repeat service use.';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    /**
     * @var array{granularity: string, labels: list<string>, newGuests: list<int>, payingGuests: list<int>, returningGuests: list<int>}
     */
    public array $trend = [
        'granularity' => 'day',
        'labels' => [],
        'newGuests' => [],
        'payingGuests' => [],
        'returningGuests' => [],
    ];

    public string $periodLabel = 'Selected period';

    /**
     * Explains the active date scope and automatic chart resolution.
     */
    public function getDescription(): string|Htmlable|null
    {
        return sprintf(
            'Guest activity for %s, grouped by %s. Repeat-service guests are counted separately in each interval.',
            $this->periodLabel,
            $this->trend['granularity'],
        );
    }

    /**
     * Restricts the trend to the same roles as the guest report.
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    /**
     * Uses all three guest series to control the chart empty state.
     */
    public function isEmpty(): bool
    {
        return collect([
            $this->trend['newGuests'],
            $this->trend['payingGuests'],
            $this->trend['returningGuests'],
        ])->flatten()->every(fn (float|int $count): bool => (int) $count === 0);
    }

    /**
     * Builds the chart datasets without database work.
     */
    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'New guest profiles',
                    'data' => $this->trend['newGuests'],
                    'borderColor' => '#059669',
                    'backgroundColor' => 'rgba(5, 150, 105, 0.14)',
                    'pointBackgroundColor' => '#059669',
                    'borderWidth' => 3,
                    'pointRadius' => 3,
                    'tension' => 0.3,
                    'fill' => false,
                ],
                [
                    'label' => 'Guests with collected payments',
                    'data' => $this->trend['payingGuests'],
                    'borderColor' => '#2563EB',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.14)',
                    'pointBackgroundColor' => '#2563EB',
                    'borderWidth' => 3,
                    'pointRadius' => 3,
                    'tension' => 0.3,
                    'fill' => false,
                ],
                [
                    'label' => 'Repeat-service guests',
                    'data' => $this->trend['returningGuests'],
                    'borderColor' => '#7C3AED',
                    'backgroundColor' => 'rgba(124, 58, 237, 0.14)',
                    'pointBackgroundColor' => '#7C3AED',
                    'borderWidth' => 3,
                    'pointRadius' => 3,
                    'tension' => 0.3,
                    'fill' => false,
                ],
            ],
            'labels' => $this->trend['labels'],
        ];
    }

    /**
     * Renders each guest metric as a line over the selected period.
     */
    protected function getType(): string
    {
        return 'line';
    }

    /**
     * Keeps dense date ranges and whole-person counts readable on smaller screens.
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
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                    'title' => ['display' => true, 'text' => 'Guest count'],
                ],
            ],
        ];
    }
}
