<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;

class MonthlyRevenueChart extends ChartWidget
{
    protected ?string $heading = 'Monthly Revenue';

    protected function getData(): array
    {
        $totals = Payment::query()
            ->selectRaw('MONTH(created_at) as month, SUM(amount) as total')
            ->whereYear('created_at', now()->year)
            ->whereIn('payment_status', ['paid', 'completed'])
            ->groupBy('month')
            ->pluck('total', 'month');

        return [
            'datasets' => [[
                'label' => 'Revenue',
                'data' => collect(range(1, 12))
                    ->map(fn (int $month) => (float) ($totals[$month] ?? 0))
                    ->all(),
                'borderColor' => '#22c55e',
                'backgroundColor' => 'rgba(34, 197, 94, 0.15)',
                'fill' => true,
            ]],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }
}
