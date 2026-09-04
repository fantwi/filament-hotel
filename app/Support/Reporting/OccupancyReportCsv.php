<?php

namespace App\Support\Reporting;

use RuntimeException;

/**
 * Serializes the occupancy report snapshot into a spreadsheet-friendly CSV.
 */
final class OccupancyReportCsv
{
    /**
     * @param  array<string, mixed>  $report
     */
    public function toCsv(array $report, string $periodLabel): string
    {
        $rows = [
            ['Occupancy Report'],
            ['Period', $periodLabel],
            ['Start date', $report['periodStart']->toDateString()],
            ['End date', $report['periodEnd']->toDateString()],
            ['Generated at', $report['snapshotAt']->format('Y-m-d H:i:s T')],
            [],
            ['Selected-period summary'],
            ['Metric', 'Value'],
            ['Hotel bookings', (string) $report['hotelBookings']],
            ['Booked room nights', (string) $report['bookedRoomNights']],
            ['Room-night capacity', (string) $report['roomNightCapacity']],
            ['Room occupancy', $this->percentage($report['occupancyRate'])],
            ['Conference bookings', (string) $report['conferenceBookings']],
            ['Table reservations', (string) $report['tableReservations']],
            [],
            ['Occupancy trend ('.$report['occupancyTrend']['granularity'].')'],
            ['Bucket', 'Occupancy rate', 'Booked room nights', 'Room-night capacity'],
        ];

        foreach ($report['occupancyTrend']['labels'] as $index => $label) {
            $rows[] = [
                $label,
                $this->percentage($report['occupancyTrend']['occupancyRates'][$index]),
                (string) $report['occupancyTrend']['bookedRoomNights'][$index],
                (string) $report['occupancyTrend']['roomNightCapacities'][$index],
            ];
        }

        $rows = [
            ...$rows,
            [],
            ['Live operational snapshot'],
            ['Snapshot at', $report['snapshotAt']->format('Y-m-d H:i:s T')],
            ['Metric', 'Value'],
            ['Total rooms', (string) $report['roomStatus']['total']],
            ['Rooms available now', (string) $report['roomStatus']['available']],
            ['Rooms occupied now', (string) $report['roomStatus']['occupied']],
            ['Rooms under maintenance', (string) $report['roomStatus']['maintenance']],
            ['Rooms in service', (string) $report['roomsInService']],
            ['Conference rooms available now', (string) $report['conferenceAvailability']['available']],
            ['Conference rooms unavailable now', (string) $report['conferenceAvailability']['unavailable']],
            ['Tables available now', (string) $report['tableStatus']['available']],
            ['Tables reserved or occupied now', (string) ($report['tableStatus']['reserved'] + $report['tableStatus']['occupied'])],
            ['Tables cleaning or under maintenance', (string) $report['tableStatus']['unavailable']],
        ];

        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new RuntimeException('Unable to create the occupancy report export.');
        }

        foreach ($rows as $row) {
            fputcsv($stream, $row, escape: '');
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        if ($csv === false) {
            throw new RuntimeException('Unable to read the occupancy report export.');
        }

        return $csv;
    }

    /**
     * Formats occupancy consistently while preserving unavailable capacity.
     */
    private function percentage(float|int|null $value): string
    {
        return $value === null ? 'N/A' : number_format((float) $value, 1).'%';
    }
}
