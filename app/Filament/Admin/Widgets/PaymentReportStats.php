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
            Payment::query()->whereBetween(PaymentReportFilters::dateColumn($filters), [$start, $end]),
            (string) ($filters['transaction_type'] ?? 'all'),
        );
        $query = PaymentReportFilters::applyStatus(
            $query,
            (string) ($filters['payment_status'] ?? 'all'),
        );
        $query = PaymentReportFilters::applyMethod(
            $query,
            (string) ($filters['payment_method'] ?? 'all'),
        );
        $scope = PaymentReportFilters::typeLabel((string) ($filters['transaction_type'] ?? 'all'));
        $status = PaymentReportFilters::statusLabel((string) ($filters['payment_status'] ?? 'all'));
        $method = PaymentReportFilters::methodLabel((string) ($filters['payment_method'] ?? 'all'));
        $dateBasis = PaymentReportFilters::dateBasisLabel((string) ($filters['date_basis'] ?? 'created_at'));
        $period = PaymentReportFilters::periodOptions()[(string) ($filters['period'] ?? 'monthly')] ?? 'Monthly';

        $count = (clone $query)->count();
        $collected = (clone $query)->whereIn('payment_status', ['paid', 'completed', 'refunded', 'refund'])->sum('amount');
        $pending = (clone $query)->whereIn('payment_status', ['pending', 'unpaid'])->sum('amount');
        $refunded = (clone $query)->whereIn('payment_status', ['refunded', 'refund'])->sum('amount');

        return [
            Stat::make('Payment count', number_format($count))
                ->description("{$scope} · {$status} · {$method} · {$period}"),
            Stat::make('Total collected', 'GHS '.number_format((float) $collected, 2))
                ->description('Gross received before refunds · '.$dateBasis)
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
