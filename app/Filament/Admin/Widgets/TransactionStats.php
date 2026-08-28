<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/**
 * Provides a transaction summary stats overview for the dashboard.
 */
class TransactionStats extends StatsOverviewWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    /**
     * Builds date-filtered transaction summary stats.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $hotel = $this->summary(Booking::query(), 'total_price', 'booking_id', ['cancelled', 'expired', 'no_show']);
        $conference = $this->summary(ConferenceBooking::query(), 'total_price', 'conference_booking_id', ['cancelled', 'no_show']);
        $tables = $this->summary(RestaurantReservation::query(), 'reservation_fee', 'restaurant_reservation_id', ['cancelled', 'no_show']);
        $food = $this->summary(RestaurantOrder::query(), 'total', 'restaurant_order_id', ['cancelled']);
        $periodLabel = $this->dashboardDateRangeLabel();

        $transactions = $hotel['transactions'] + $conference['transactions'] + $tables['transactions'] + $food['transactions'];
        $gross = $hotel['gross'] + $conference['gross'] + $tables['gross'] + $food['gross'];
        $payments = $hotel['payments'] + $conference['payments'] + $tables['payments'] + $food['payments'];
        $outstanding = $hotel['outstanding'] + $conference['outstanding'] + $tables['outstanding'] + $food['outstanding'];
        $corporateOutstanding = $hotel['corporate_outstanding'] + $conference['corporate_outstanding'] + $tables['corporate_outstanding'] + $food['corporate_outstanding'];

        return [
            Stat::make('Transactions created', number_format($transactions))
                ->description($periodLabel)
                ->icon('heroicon-o-arrows-right-left')
                ->color('primary'),
            Stat::make('Gross transaction value', $this->formatAmount($gross))
                ->description('Excludes cancelled transactions')
                ->icon('heroicon-o-banknotes')
                ->color('info'),
            Stat::make('Payments received', $this->formatAmount($payments))
                ->description($periodLabel)
                ->icon('heroicon-o-credit-card')
                ->color('success'),
            Stat::make('Outstanding balance', $this->formatAmount($outstanding))
                ->description('Unpaid transactions in range')
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Corporate outstanding', $this->formatAmount($corporateOutstanding))
                ->description('Included in outstanding balance')
                ->icon('heroicon-o-building-office-2')
                ->color('danger'),
        ];
    }

    /**
     * Summarizes a transaction type and its linked payments for the selected period.
     *
     * @param  array<int, string>  $excludedStatuses
     * @return array<string, int|float>
     */
    private function summary(Builder $transactions, string $amountColumn, string $paymentForeignKey, array $excludedStatuses): array
    {
        $transactionsInRange = $this->forDashboardDateRange($transactions);
        $activeTransactions = (clone $transactionsInRange)->whereNotIn('status', $excludedStatuses);
        $outstandingTransactions = (clone $activeTransactions)->whereIn('payment_status', ['pending', 'unpaid']);
        $paymentsInRange = $this->forDashboardDateRange(Payment::query())
            ->whereNotNull($paymentForeignKey)
            ->whereIn('payment_status', ['paid', 'completed']);

        return [
            'transactions' => (clone $transactionsInRange)->count(),
            'gross' => (float) (clone $activeTransactions)->sum($amountColumn),
            'payments' => (float) (clone $paymentsInRange)->sum('amount'),
            'outstanding' => (float) (clone $outstandingTransactions)->sum($amountColumn),
            'corporate_outstanding' => (float) (clone $outstandingTransactions)
                ->whereNotNull('corporate_organization_id')
                ->sum($amountColumn),
        ];
    }

    /**
     * Formats an amount using the application's display currency.
     */
    private function formatAmount(float|int|string|null $amount): string
    {
        return 'GHS '.number_format((float) $amount, 2);
    }
}
