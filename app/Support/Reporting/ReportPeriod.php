<?php

namespace App\Support\Reporting;

use Carbon\Carbon;
use InvalidArgumentException;
use Throwable;

/**
 * Defines the shared vocabulary and date boundaries used by admin reports.
 */
final class ReportPeriod
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
            'custom' => 'Custom range',
        ];
    }

    /**
     * Resolves an inclusive reporting range ending today for presets.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function range(string $period, ?string $start = null, ?string $end = null): array
    {
        if (! array_key_exists($period, self::options())) {
            throw new InvalidArgumentException("Unsupported report period [{$period}].");
        }

        if ($period === 'custom') {
            if (blank($start) || blank($end)) {
                throw new InvalidArgumentException('A custom report period requires start and end dates.');
            }

            try {
                $rangeStart = Carbon::parse($start)->startOfDay();
                $rangeEnd = Carbon::parse($end)->endOfDay();
            } catch (Throwable $exception) {
                throw new InvalidArgumentException('The custom report period contains an invalid date.', previous: $exception);
            }

            if ($rangeStart->greaterThan($rangeEnd)) {
                throw new InvalidArgumentException('The report start date must be before or equal to the end date.');
            }

            return [$rangeStart, $rangeEnd];
        }

        $now = now();
        $rangeStart = match ($period) {
            'daily' => $now->copy()->startOfDay(),
            'weekly' => $now->copy()->startOfWeek(),
            'quarterly' => $now->copy()->startOfQuarter(),
            'yearly' => $now->copy()->startOfYear(),
            default => $now->copy()->startOfMonth(),
        };

        return [$rangeStart, $now->copy()->endOfDay()];
    }

    /**
     * Formats a stable label for the selected preset or custom range.
     */
    public static function label(string $period, ?string $start = null, ?string $end = null): string
    {
        if ($period !== 'custom') {
            return self::options()[$period]
                ?? throw new InvalidArgumentException("Unsupported report period [{$period}].");
        }

        [$rangeStart, $rangeEnd] = self::range($period, $start, $end);

        return $rangeStart->isSameDay($rangeEnd)
            ? $rangeStart->format('M j, Y')
            : $rangeStart->format('M j, Y').' to '.$rangeEnd->format('M j, Y');
    }
}
