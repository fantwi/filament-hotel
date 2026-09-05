<?php

namespace App\Support\Reporting;

use RuntimeException;

/**
 * Serializes an applied guest report into a spreadsheet-friendly CSV.
 */
final class GuestReportCsv
{
    /** @var array<string, string> */
    private const SERVICE_LABELS = [
        'hotel' => 'Hotel bookings',
        'conference' => 'Conference bookings',
        'table' => 'Table reservations',
        'food' => 'Food orders',
        'other' => 'Direct or uncategorized payments',
    ];

    /**
     * @param  array<string, mixed>  $report
     */
    public function toCsv(array $report, string $periodLabel): string
    {
        $rows = [
            ['Guest Report'],
            ['Period', $periodLabel],
            ['Start date', $report['periodStart']->toDateString()],
            ['End date', $report['periodEnd']->toDateString()],
            ['Generated at', $report['generatedAt']->format('Y-m-d H:i:s T')],
            [],
            ['Guest overview'],
            ['Metric', 'Value', 'Additional detail'],
            ['All-time guest profiles', (string) $report['totalGuests'], ''],
            ['New guest profiles', (string) $report['newGuests'], ''],
            ['Guests with collected payments', (string) $report['payingGuests'], ''],
            ['Repeat-service guests', (string) $report['returningGuests'], ''],
            ['Average collected per paying guest (GHS)', $this->amount($report['averageSpend']), ''],
            ['Gross guest revenue (GHS)', $this->amount($report['totalPaid']), $this->countLabel($report['paymentCount'], 'payment')],
            ['Refunds processed (GHS)', $this->amount($report['refundTotal']), $this->countLabel($report['refundCount'], 'refund')],
            ['Net guest revenue (GHS)', $this->amount($report['netSpend']), ''],
            [],
            ['Paid service mix'],
            ['Service', 'Collected payments', 'Share of payments'],
        ];

        foreach ($report['activity'] as $service => $count) {
            $rows[] = [
                self::SERVICE_LABELS[$service] ?? $this->label((string) $service),
                (string) $count,
                $this->share($count, $report['paymentCount']),
            ];
        }

        $rows = [
            ...$rows,
            [],
            ['Previous-period comparison', $report['comparison']['previousPeriodLabel']],
            ['Metric', 'Selected period', 'Previous period', 'Difference', 'Change'],
        ];

        foreach ([
            'newGuests' => 'New guest profiles',
            'payingGuests' => 'Guests with collected payments',
            'returningGuests' => 'Repeat-service guests',
        ] as $metric => $label) {
            $comparison = $report['comparison'][$metric];
            $rows[] = [
                $label,
                (string) $comparison['current'],
                (string) $comparison['previous'],
                (string) $comparison['difference'],
                $this->percentage($comparison['percentageChange']),
            ];
        }

        $rows = [
            ...$rows,
            [],
            ['Guest activity over time ('.$report['trend']['granularity'].')'],
            ['Bucket', 'New guest profiles', 'Guests with collected payments', 'Repeat-service guests'],
        ];

        foreach ($report['trend']['labels'] as $index => $label) {
            $rows[] = [
                $label,
                (string) $report['trend']['newGuests'][$index],
                (string) $report['trend']['payingGuests'][$index],
                (string) $report['trend']['returningGuests'][$index],
            ];
        }

        $rows = [
            ...$rows,
            [],
            ['Top guests by gross revenue'],
            ['Guest', 'Email', 'Collected payments', 'Gross revenue (GHS)'],
        ];

        foreach ($report['topGuests'] as $entry) {
            $guest = $entry->guest ?? null;
            $rows[] = [
                $this->safeSpreadsheetText((string) ($guest?->full_name ?? 'Guest not recorded')),
                $this->safeSpreadsheetText((string) ($guest?->email ?? 'Email not recorded')),
                (string) $entry->payment_count,
                $this->amount($entry->total_spend),
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
            throw new RuntimeException('Unable to create the guest report export.');
        }

        foreach ($rows as $row) {
            fputcsv($stream, $row, escape: '');
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        if ($csv === false) {
            throw new RuntimeException('Unable to read the guest report export.');
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

    /**
     * Prevents guest-controlled text from being interpreted as a spreadsheet formula.
     */
    private function safeSpreadsheetText(string $value): string
    {
        return preg_match('/^[\x00-\x20]*[=+\-@]/', $value) === 1
            ? "'".$value
            : $value;
    }
}
