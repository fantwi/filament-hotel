<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Payment;
use App\Services\PaymentReportFilters;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Displays payment totals for the active transaction type and date range.
 */
class PaymentReportStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'accountant']) ?? false;
    }

    /**
     * Builds the four payment metrics from the same scope as the payments table.
     *
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $filters = $this->pageFilters ?? [];
        [$start, $end] = PaymentReportFilters::dateRange($filters);
        $query = PaymentReportFilters::applyType(
            Payment::query()->whereBetween('created_at', [$start, $end]),
            (string) ($filters['transaction_type'] ?? 'all'),
        );
        $scope = PaymentReportFilters::typeLabel((string) ($filters['transaction_type'] ?? 'all'));
        $period = PaymentReportFilters::periodOptions()[(string) ($filters['period'] ?? 'monthly')] ?? 'Monthly';

        $count = (clone $query)->count();
        $collected = (clone $query)->whereIn('payment_status', ['paid', 'completed'])->sum('amount');
        $pending = (clone $query)->whereIn('payment_status', ['pending', 'unpaid'])->sum('amount');
        $refunded = (clone $query)->whereIn('payment_status', ['refunded', 'refund'])->sum('amount');

        return [
            Stat::make('Payment count', number_format($count))
                ->description("{$scope} · {$period}"),
            Stat::make('Total collected', 'GHS '.number_format((float) $collected, 2))
                ->description('Paid and completed payments')
                ->color('success'),
            Stat::make('Pending amount', 'GHS '.number_format((float) $pending, 2))
                ->description('Pending or unpaid payments')
                ->color('warning'),
            Stat::make('Refunded amount', 'GHS '.number_format((float) $refunded, 2))
                ->description('Refunded payments')
                ->color('danger'),
        ];
    }
}
