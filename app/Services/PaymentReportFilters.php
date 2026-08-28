<?php

namespace App\Services;

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
        ];
    }

    /**
     * Returns the date breakdown choices shown above the payments table.
     *
     * @return array<string, string>
     */
    public static function periodOptions(): array
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Annually',
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
        $now = now();
        [$fallbackStart, $fallbackEnd] = match ((string) ($filters['period'] ?? 'monthly')) {
            'daily' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'weekly' => [$now->copy()->startOfWeek(), $now->copy()->endOfDay()],
            'quarterly' => [$now->copy()->startOfQuarter(), $now->copy()->endOfDay()],
            'yearly' => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
        };

        try {
            $start = filled($filters['start_date'] ?? null)
                ? Carbon::parse($filters['start_date'])->startOfDay()
                : $fallbackStart;
            $end = filled($filters['end_date'] ?? null)
                ? Carbon::parse($filters['end_date'])->endOfDay()
                : $fallbackEnd;
        } catch (\Throwable) {
            return [$fallbackStart, $fallbackEnd];
        }

        return $start->greaterThan($end)
            ? [$end->copy()->startOfDay(), $start->copy()->endOfDay()]
            : [$start, $end];
    }

    /**
     * Applies a transaction type constraint to a payments query.
     */
    public static function applyType(Builder $query, string $type): Builder
    {
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
     * Returns a display label for the selected transaction type.
     */
    public static function typeLabel(string $type): string
    {
        return self::typeOptions()[$type] ?? self::typeOptions()['all'];
    }
}
