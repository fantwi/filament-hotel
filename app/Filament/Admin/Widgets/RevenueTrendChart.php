<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Charts collected revenue, refunds, and net revenue from prepared report data.
 */
class RevenueTrendChart extends ChartWidget
{
    protected ?string $heading = 'Revenue trend';

    protected ?string $emptyStateHeading = 'No revenue activity for this period';

    protected ?string $emptyStateDescription = 'Choose another reporting period to view collections and refunds.';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    /**
     * @var array{granularity: string, labels: list<string>, collected: list<float>, refunds: list<float>, net: list<float>}
     */
    public array $trend = [
        'granularity' => 'day',
        'labels' => [],
        'collected' => [],
        'refunds' => [],
        'net' => [],
    ];

    public string $periodLabel = 'Selected period';

    /**
     * Explains the active date scope and automatic chart resolution.
     */
    public function getDescription(): string|Htmlable|null
    {
        return sprintf(
            '%s collections and refunds grouped by %s.',
            $this->periodLabel,
            $this->trend['granularity'],
        );
    }

    /**
     * Restricts the trend to the same roles as the revenue report.
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    /**
     * Uses actual financial movement to control the chart empty state.
     */
    public function isEmpty(): bool
    {
        return collect([$this->trend['collected'], $this->trend['refunds']])
            ->flatten()
            ->every(fn (float|int $amount): bool => (float) $amount === 0.0);
    }

    /**
     * Builds the mixed bar and line chart without database work.
     */
    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Collected revenue',
                    'data' => $this->trend['collected'],
                    'borderColor' => '#059669',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.72)',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
                [
                    'label' => 'Refunds',
                    'data' => $this->trend['refunds'],
                    'borderColor' => '#E11D48',
                    'backgroundColor' => 'rgba(244, 63, 94, 0.62)',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
                [
                    'label' => 'Net revenue',
                    'data' => $this->trend['net'],
                    'type' => 'line',
                    'borderColor' => '#4F46E5',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.16)',
                    'pointBackgroundColor' => '#4338CA',
                    'pointBorderColor' => '#FFFFFF',
                    'pointRadius' => 3,
                    'borderWidth' => 3,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $this->trend['labels'],
        ];
    }

    /**
     * Renders collections and refunds as bars with net revenue overlaid as a line.
     */
    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Keeps dense date ranges and monetary values readable on smaller screens.
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
                    'title' => ['display' => true, 'text' => 'Amount (GHS)'],
                ],
            ],
        ];
    }
}
