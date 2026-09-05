<?php

namespace App\Support\Reporting;

use RuntimeException;

/**
 * Serializes an applied restaurant order report into a spreadsheet-friendly CSV.
 */
final class RestaurantOrderReportCsv
{
    /** @var array<string, string> */
    private const CHANNEL_LABELS = [
        'web' => 'Website',
        'qr' => 'Table QR',
        'staff' => 'Staff entry',
    ];

    /** @var array<string, string> */
    private const PAYMENT_STATUS_LABELS = [
        'completed' => 'Completed',
        'paid' => 'Paid',
        'pending' => 'Pending',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
        'refund' => 'Refunded',
    ];

    /**
     * @param  array<string, mixed>  $report
     */
    public function toCsv(array $report, string $periodLabel): string
    {
        $rows = [
            ['Restaurant Order Report'],
            ['Period', $periodLabel],
            ['Start date', $report['periodStart']->toDateString()],
            ['End date', $report['periodEnd']->toDateString()],
            ['Generated at', $report['generatedAt']->format('Y-m-d H:i:s T')],
            [],
            ['Restaurant order overview'],
            ['Metric', 'Value', 'Additional detail'],
            ['Orders received', (string) $report['totalOrders'], ''],
            ['Items ordered', (string) $report['totalItems'], ''],
            ['Paid orders', (string) $report['paidOrders'], ''],
            ['Awaiting payment', (string) $report['pendingOrders'], ''],
            ['Cancelled orders', (string) $report['cancelledOrders'], ''],
            ['Active orders in period', (string) $report['activeOrders'], ''],
            ['Live kitchen queue (current)', (string) $report['liveKitchenOrders'], 'Not limited to the selected period'],
            ['Gross collections (GHS)', $this->amount($report['revenue']), ''],
            ['Refunds processed (GHS)', $this->amount($report['refunds']), ''],
            ['Net revenue (GHS)', $this->amount($report['netRevenue']), ''],
            ['Outstanding balance (GHS)', $this->amount($report['outstanding']), ''],
            ['Average collected order (GHS)', $this->amount($report['averageOrderValue']), ''],
            ['Payment rate', $this->percentage($report['paymentRate']), ''],
            [],
            ['Previous-period comparison', $report['comparison']['previousPeriodLabel']],
            ['Metric', 'Selected period', 'Previous period', 'Difference', 'Change'],
        ];

        foreach ([
            'orders' => ['Orders received', false],
            'items' => ['Items ordered', false],
            'netRevenue' => ['Net revenue (GHS)', true],
        ] as $metric => [$label, $isAmount]) {
            $comparison = $report['comparison'][$metric];
            $rows[] = [
                $label,
                $isAmount ? $this->amount($comparison['current']) : (string) $comparison['current'],
                $isAmount ? $this->amount($comparison['previous']) : (string) $comparison['previous'],
                $isAmount ? $this->amount($comparison['difference']) : (string) $comparison['difference'],
                $this->nullablePercentage($comparison['percentageChange']),
            ];
        }

        $rows = [
            ...$rows,
            [],
            ['Restaurant activity over time ('.$report['trend']['granularity'].')'],
            ['Bucket', 'Orders', 'Items', 'Gross collections (GHS)', 'Refunds (GHS)', 'Net revenue (GHS)'],
        ];

        foreach ($report['trend']['labels'] as $index => $label) {
            $rows[] = [
                $this->safeSpreadsheetText((string) $label),
                (string) $report['trend']['orders'][$index],
                (string) $report['trend']['items'][$index],
                $this->amount($report['trend']['collected'][$index]),
                $this->amount($report['trend']['refunds'][$index]),
                $this->amount($report['trend']['netRevenue'][$index]),
            ];
        }

        $rows = [
            ...$rows,
            [],
            ['Complete order register'],
            ['Order reference', 'Guest', 'Email', 'Channel', 'Fulfilment', 'Payment', 'Items', 'Total (GHS)', 'Created at'],
        ];

        foreach ($report['orders'] as $order) {
            $guestName = $order->guest?->full_name ?: 'Walk-in guest';
            $email = $order->guest?->email ?: $order->customer_email ?: 'Email not recorded';
            $channel = filled($order->ordering_channel) ? $order->ordering_channel : 'web';

            $rows[] = [
                $this->safeSpreadsheetText((string) $order->order_number),
                $this->safeSpreadsheetText((string) $guestName),
                $this->safeSpreadsheetText((string) $email),
                $this->safeSpreadsheetText(self::CHANNEL_LABELS[$channel] ?? $this->label((string) $channel)),
                $this->safeSpreadsheetText($this->label((string) $order->status)),
                $this->safeSpreadsheetText(self::PAYMENT_STATUS_LABELS[$order->payment_status] ?? $this->label((string) $order->payment_status)),
                (string) ($order->items_sum_quantity ?? 0),
                $this->amount($order->total),
                $order->created_at?->format('Y-m-d H:i:s') ?? '',
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
            throw new RuntimeException('Unable to create the restaurant report export.');
        }

        foreach ($rows as $row) {
            fputcsv($stream, $row, escape: '');
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        if ($csv === false) {
            throw new RuntimeException('Unable to read the restaurant report export.');
        }

        return $csv;
    }

    private function amount(float|int|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function percentage(float|int|string $value): string
    {
        return number_format((float) $value, 1).'%';
    }

    private function nullablePercentage(float|int|null $value): string
    {
        return $value === null ? 'N/A' : $this->percentage($value);
    }

    private function label(string $value): string
    {
        return ucfirst(str_replace('_', ' ', $value));
    }

    /**
     * Prevents externally controlled text from being interpreted as a spreadsheet formula.
     */
    private function safeSpreadsheetText(string $value): string
    {
        return preg_match('/^[\x00-\x20]*[=+\-@]/', $value) === 1
            ? "'".$value
            : $value;
    }
}
