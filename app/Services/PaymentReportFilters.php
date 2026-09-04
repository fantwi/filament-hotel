<?php

namespace App\Services;

use App\Support\Reporting\ReportPeriod;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Centralizes payment report filter options and query constraints.
 */
final class PaymentReportFilters
{
    /**
     * Returns the transaction type choices shown above the payments table.
     *
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            'all' => 'All payments',
            'food_orders' => 'Food orders',
            'conference_bookings' => 'Conference bookings',
            'hotel_bookings' => 'Hotel bookings',
            'table_reservations' => 'Table reservations',
            'other' => 'Other / direct payments',
        ];
    }

    /**
     * Returns the date breakdown choices shown above the payments table.
     *
     * @return array<string, string>
     */
    public static function periodOptions(): array
    {
        return array_diff_key(ReportPeriod::options(), ['custom' => true]);
    }

    /**
     * Returns payment-state groupings used by the register and dashboard links.
     *
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            'all' => 'All statuses',
            'revenue' => 'Revenue received',
            'collected' => 'Collected',
            'pending' => 'Pending or unpaid',
            'refunded' => 'Refunded',
        ];
    }

    /**
     * Returns payment methods supported by the persisted payment enum.
     *
     * @return array<string, string>
     */
    public static function methodOptions(): array
    {
        return [
            'all' => 'All methods',
            'cash' => 'Cash',
            'momo' => 'Mobile money',
            'card' => 'Card',
            'paystack' => 'Paystack',
            'corporate_account' => 'Corporate account',
            'bank_transfer' => 'Bank transfer',
        ];
    }

    /**
     * Returns the timestamp choices needed by collection and refund drill-downs.
     *
     * @return array<string, string>
     */
    public static function dateBasisOptions(): array
    {
        return [
            'created_at' => 'Payment recorded date',
            'refunded_at' => 'Refund processed date',
        ];
    }

    /**
     * Resolves a preset or custom date range for payment reporting.
     *
     * @param  array<string, mixed>  $filters
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function dateRange(array $filters = []): array
    {
        $period = array_key_exists((string) ($filters['period'] ?? ''), self::periodOptions())
            ? (string) $filters['period']
            : 'monthly';
        [$fallbackStart, $fallbackEnd] = ReportPeriod::range($period);

        try {
            if (filled($filters['start_date'] ?? null) && filled($filters['end_date'] ?? null)) {
                return ReportPeriod::range('custom', $filters['start_date'], $filters['end_date']);
            }
        } catch (\Throwable) {
            return [$fallbackStart, $fallbackEnd];
        }

        return [$fallbackStart, $fallbackEnd];
    }

    /**
     * Applies a transaction type constraint to a payments query.
     */
    public static function applyType(Builder $query, string $type): Builder
    {
        if ($type === 'other') {
            return $query
                ->whereNull('booking_id')
                ->whereNull('conference_booking_id')
                ->whereNull('restaurant_reservation_id')
                ->whereNull('restaurant_order_id');
        }

        $column = match ($type) {
            'food_orders' => 'restaurant_order_id',
            'conference_bookings' => 'conference_booking_id',
            'hotel_bookings' => 'booking_id',
            'table_reservations' => 'restaurant_reservation_id',
            default => null,
        };

        return $column === null ? $query : $query->whereNotNull($column);
    }

    /**
     * Applies the normalized payment-state grouping selected by staff.
     */
    public static function applyStatus(Builder $query, string $status): Builder
    {
        return match ($status) {
            'revenue' => $query->whereIn('payment_status', ['paid', 'completed', 'refunded', 'refund']),
            'collected' => $query->whereIn('payment_status', ['paid', 'completed']),
            'pending' => $query->whereIn('payment_status', ['pending', 'unpaid']),
            'refunded' => $query->whereIn('payment_status', ['refunded', 'refund']),
            default => $query,
        };
    }

    /**
     * Applies an allowed payment-method constraint.
     */
    public static function applyMethod(Builder $query, string $method): Builder
    {
        return $method !== 'all' && array_key_exists($method, self::methodOptions())
            ? $query->where('method', $method)
            : $query;
    }

    /**
     * Resolves the validated timestamp column used by the active date range.
     */
    public static function dateColumn(array $filters): string
    {
        $basis = (string) ($filters['date_basis'] ?? 'created_at');

        return array_key_exists($basis, self::dateBasisOptions()) ? $basis : 'created_at';
    }

    /**
     * Returns a display label for the selected transaction type.
     */
    public static function typeLabel(string $type): string
    {
        return self::typeOptions()[$type] ?? self::typeOptions()['all'];
    }

    /**
     * Returns a display label for the selected payment-state grouping.
     */
    public static function statusLabel(string $status): string
    {
        return self::statusOptions()[$status] ?? self::statusOptions()['all'];
    }

    /**
     * Returns a display label for the selected payment method.
     */
    public static function methodLabel(string $method): string
    {
        return self::methodOptions()[$method] ?? self::methodOptions()['all'];
    }

    /**
     * Returns a display label for the active payment timestamp.
     */
    public static function dateBasisLabel(string $basis): string
    {
        return self::dateBasisOptions()[$basis] ?? self::dateBasisOptions()['created_at'];
    }
}
