<?php

namespace App\Support\Reporting;

use RuntimeException;

/**
 * Serializes an applied revenue report into a spreadsheet-friendly CSV.
 */
final class RevenueReportCsv
{
    /** @var array<string, string> */
    private const CHANNEL_LABELS = [
        'hotel' => 'Hotel bookings',
        'conference' => 'Conference bookings',
        'table' => 'Table reservations',
        'food' => 'Food orders',
        'other' => 'Other / direct',
    ];

    /** @var array<string, string> */
    private const METHOD_LABELS = [
        'cash' => 'Cash',
        'momo' => 'Mobile money',
        'card' => 'Card',
        'paystack' => 'Paystack',
        'corporate_account' => 'Corporate account',
        'bank_transfer' => 'Bank transfer',
    ];

    /**
     * @param  array<string, mixed>  $report
     */
    public function toCsv(array $report, string $periodLabel): string
    {
        $rows = [
            ['Revenue Report'],
            ['Period', $periodLabel],
            ['Start date', $report['periodStart']->toDateString()],
            ['End date', $report['periodEnd']->toDateString()],
            ['Generated at', $report['generatedAt']->format('Y-m-d H:i:s T')],
            [],
            ['Selected-period summary'],
            ['Metric', 'Amount (GHS)', 'Count'],
            ['Revenue received', $this->amount($report['revenue']), $this->countLabel($report['paymentsReceived'], 'payment')],
            ['Refunds processed', $this->amount($report['refunds']), $this->countLabel($report['refundCount'], 'refund')],
            ['Net revenue', $this->amount($report['netRevenue'])],
            ['Outstanding balance', $this->amount($report['outstanding'])],
            [],
            ['Revenue by business channel'],
            ['Channel', 'Completed payments', 'Amount (GHS)', 'Share of revenue'],
        ];

        foreach ($report['revenueByChannel'] as $channel => $values) {
            $rows[] = [
                self::CHANNEL_LABELS[$channel] ?? $this->label($channel),
                (string) $values['payment_count'],
                $this->amount($values['total']),
                $this->share($values['total'], $report['revenue']),
            ];
        }

        $rows = [
            ...$rows,
            [],
            ['Previous-period comparison', $report['comparison']['previousPeriodLabel']],
            ['Metric', 'Current (GHS)', 'Previous (GHS)', 'Difference (GHS)', 'Change'],
        ];

        foreach ([
            'revenue' => 'Revenue received',
            'refunds' => 'Refunds processed',
            'netRevenue' => 'Net revenue',
        ] as $metric => $label) {
            $comparison = $report['comparison'][$metric];
            $rows[] = [
                $label,
                $this->amount($comparison['current']),
                $this->amount($comparison['previous']),
                $this->amount($comparison['difference']),
                $this->percentage($comparison['percentageChange']),
            ];
        }

        $rows = [
            ...$rows,
            [],
            ['Revenue trend ('.$report['trend']['granularity'].')'],
            ['Bucket', 'Collected (GHS)', 'Refunds (GHS)', 'Net revenue (GHS)'],
        ];

        foreach ($report['trend']['labels'] as $index => $label) {
            $rows[] = [
                $label,
                $this->amount($report['trend']['collected'][$index]),
                $this->amount($report['trend']['refunds'][$index]),
                $this->amount($report['trend']['net'][$index]),
            ];
        }

        $rows = [
            ...$rows,
            [],
            ['Outstanding by transaction'],
            ['Transaction type', 'Amount (GHS)', 'Share of outstanding'],
        ];

        foreach ($report['outstandingBreakdown'] as $channel => $amount) {
            $rows[] = [
                self::CHANNEL_LABELS[$channel] ?? $this->label($channel),
                $this->amount($amount),
                $this->share($amount, $report['outstanding']),
            ];
        }

        $rows = [
            ...$rows,
            [],
            ['Payment methods'],
            ['Method', 'Completed payments', 'Amount (GHS)', 'Share of revenue'],
        ];

        foreach ($report['methods'] as $method) {
            $methodKey = (string) ($method->method ?? '');
            $rows[] = [
                self::METHOD_LABELS[$methodKey] ?? $this->label($methodKey ?: 'unknown'),
                (string) $method->payment_count,
                $this->amount($method->total),
                $this->share($method->total, $report['revenue']),
            ];
        }

        return $this->encode($rows);
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function encode(array $rows): string
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new RuntimeException('Unable to create the revenue report export.');
        }

        foreach ($rows as $row) {
            fputcsv($stream, $row, escape: '');
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        if ($csv === false) {
            throw new RuntimeException('Unable to read the revenue report export.');
        }

        return $csv;
    }

    private function amount(float|int|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function countLabel(float|int|string $count, string $singular): string
    {
        $count = (int) $count;

        return $count.' '.($count === 1 ? $singular : $singular.'s');
    }

    private function label(string $value): string
    {
        return ucfirst(str_replace('_', ' ', $value));
    }

    private function percentage(float|int|null $value): string
    {
        return $value === null ? 'N/A' : number_format((float) $value, 1).'%';
    }

    private function share(float|int|string $value, float|int|string $total): string
    {
        $total = (float) $total;
        $percentage = $total > 0 ? ((float) $value / $total) * 100 : 0;

        return $this->percentage($percentage);
    }
}
