<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages\RestaurantOrderReport;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Charts restaurant order and item volume from report-prepared data.
 */
class RestaurantOrderVolumeTrendChart extends ChartWidget
{
    protected ?string $heading = 'Order volume trend';

    protected ?string $emptyStateHeading = 'No order volume for this period';

    protected ?string $emptyStateDescription = 'Choose another reporting period to view order and item volume.';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    /** @var array<string, mixed> */
    public array $trend = [
        'granularity' => 'day',
        'labels' => [],
        'orders' => [],
        'items' => [],
        'collected' => [],
        'refunds' => [],
        'netRevenue' => [],
    ];

    public string $periodLabel = 'Selected period';

    /**
     * Explains the active range and automatic chart resolution.
     */
    public function getDescription(): string|Htmlable|null
    {
        return sprintf(
            '%s orders and item quantities grouped by %s.',
            $this->periodLabel,
            $this->trend['granularity'],
        );
    }

    /**
     * Restricts the widget to the same roles as the restaurant report.
     */
    public static function canView(): bool
    {
        return RestaurantOrderReport::canAccess();
    }

    /**
     * Uses both volume series to control the chart empty state.
     */
    public function isEmpty(): bool
    {
        return collect([$this->trend['orders'], $this->trend['items']])
            ->flatten()
            ->every(fn (float|int $count): bool => (int) $count === 0);
    }

    /**
     * Builds both bar series without running database queries.
     */
    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $this->trend['orders'],
                    'borderColor' => '#2563EB',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.72)',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
                [
                    'label' => 'Items ordered',
                    'data' => $this->trend['items'],
                    'borderColor' => '#7C3AED',
                    'backgroundColor' => 'rgba(124, 58, 237, 0.62)',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $this->trend['labels'],
        ];
    }

    /**
     * Renders order and item counts as paired bars.
     */
    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Keeps dense periods and whole-number counts readable on smaller screens.
     */
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => ['usePointStyle' => true, 'boxWidth' => 8],
                ],
            ],
            'scales' => [
                'x' => [
                    'ticks' => ['autoSkip' => true, 'maxTicksLimit' => 12, 'maxRotation' => 0],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                    'title' => ['display' => true, 'text' => 'Order and item count'],
                ],
            ],
        ];
    }
}
