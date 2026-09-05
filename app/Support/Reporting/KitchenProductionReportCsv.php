<?php

namespace App\Support\Reporting;

use RuntimeException;

/**
 * Serializes the applied kitchen production report into a spreadsheet-friendly CSV.
 */
final class KitchenProductionReportCsv
{
    /** @var array<string, string> */
    private const STOCK_STATUS_LABELS = [
        'healthy' => 'Healthy',
        'low' => 'Low balance',
        'negative' => 'Below zero',
    ];

    /**
     * @param  array<string, mixed>  $report
     */
    public function toCsv(array $report, string $periodLabel): string
    {
        $summary = $report['summary'];
        $rows = [
            ['Kitchen Production vs Sales Report'],
            ['Period', $periodLabel],
            ['Start date', $report['periodStart']->toDateString()],
            ['End date', $report['periodEnd']->toDateString()],
            ['Generated at', $report['generatedAt']->format('Y-m-d H:i:s T')],
            [],
            ['Production overview'],
            ['Metric', 'Value'],
            ['Tracked menu items', (string) $summary['tracked_items']],
            ['Healthy stock', (string) $summary['healthy_items']],
            ['Low or below zero', (string) $summary['low_stock_items']],
            ['Negative period variance', (string) $summary['negative_variance_items']],
            ['Gross allocated collections (GHS)', $this->amount($summary['collected_revenue'])],
            ['Allocated refunds (GHS)', $this->amount($summary['refunded_revenue'])],
            ['Tracked-item net revenue (GHS)', $this->amount($summary['net_revenue'])],
            [],
            ['Filtered production register'],
            [
                'Menu item',
                'Category',
                'Unit',
                'Usage per sale',
                'Produced',
                'Wasted',
                'Net produced',
                'Units sold',
                'Amount sold',
                'Opening balance',
                'Period variance',
                'Closing balance',
                'Sell-through',
                'Gross collections (GHS)',
                'Refunds (GHS)',
                'Net revenue (GHS)',
                'Low-stock threshold',
                'Closing stock',
            ],
        ];

        foreach ($report['rows'] as $row) {
            $stockStatus = (string) ($row['stock_status'] ?? $row['status'] ?? '');
            $rows[] = [
                $this->safeSpreadsheetText((string) $row['name']),
                $this->safeSpreadsheetText((string) $row['category']),
                $this->safeSpreadsheetText((string) $row['unit']),
                $this->quantity($row['usage_per_sale']),
                $this->quantity($row['produced']),
                $this->quantity($row['wasted']),
                $this->quantity($row['net_produced']),
                (string) $row['sold_units'],
                $this->quantity($row['production_amount_sold']),
                $this->quantity($row['opening_balance']),
                $this->quantity($row['period_variance']),
                $this->quantity($row['closing_balance']),
                $row['sell_through'] === null ? 'N/A' : $this->percentage($row['sell_through']),
                $this->amount($row['collected_revenue']),
                $this->amount($row['refunded_revenue']),
                $this->amount($row['net_revenue']),
                $this->quantity($row['low_stock_threshold']),
                $this->safeSpreadsheetText(self::STOCK_STATUS_LABELS[$stockStatus] ?? ucfirst($stockStatus)),
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
            throw new RuntimeException('Unable to create the kitchen production report export.');
        }

        foreach ($rows as $row) {
            fputcsv($stream, $row, escape: '');
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        if ($csv === false) {
            throw new RuntimeException('Unable to read the kitchen production report export.');
        }

        return $csv;
    }

    private function amount(float|int|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function quantity(float|int|string $value): string
    {
        return number_format((float) $value, 3, '.', '');
    }

    private function percentage(float|int|string $value): string
    {
        return number_format((float) $value, 1).'%';
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
