<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\OccupancyReport;
use App\Models\User;
use App\Support\Reporting\OccupancyReportCsv;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class OccupancyReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_export_contains_the_selected_period_trend_and_timestamped_live_snapshot(): void
    {
        $report = [
            'periodStart' => Carbon::parse('2026-09-01')->startOfDay(),
            'periodEnd' => Carbon::parse('2026-09-04')->endOfDay(),
            'snapshotAt' => Carbon::parse('2026-09-04 12:34:56', 'UTC'),
            'roomStatus' => [
                'total' => 4,
                'occupied' => 1,
                'available' => 2,
                'maintenance' => 1,
            ],
            'roomsInService' => 3,
            'hotelBookings' => 2,
            'bookedRoomNights' => 3,
            'roomNightCapacity' => 8,
            'occupancyRate' => 37.5,
            'occupancyTrend' => [
                'granularity' => 'day',
                'labels' => ['Sep 1', 'Sep 2'],
                'bookedRoomNights' => [1, 2],
                'roomNightCapacities' => [4, 4],
                'occupancyRates' => [25.0, 50.0],
            ],
            'conferenceBookings' => 5,
            'tableReservations' => 7,
            'conferenceAvailability' => ['available' => 2, 'unavailable' => 1],
            'tableStatus' => [
                'available' => 6,
                'reserved' => 2,
                'occupied' => 1,
                'unavailable' => 1,
            ],
        ];

        $csv = (new OccupancyReportCsv)->toCsv($report, 'Sep 1, 2026 to Sep 4, 2026');
        $rows = $this->csvRows($csv);

        self::assertContains(['Occupancy Report'], $rows);
        self::assertContains(['Period', 'Sep 1, 2026 to Sep 4, 2026'], $rows);
        self::assertContains(['Start date', '2026-09-01'], $rows);
        self::assertContains(['End date', '2026-09-04'], $rows);
        self::assertContains(['Hotel bookings', '2'], $rows);
        self::assertContains(['Room occupancy', '37.5%'], $rows);
        self::assertContains(['Conference bookings', '5'], $rows);
        self::assertContains(['Table reservations', '7'], $rows);
        self::assertContains(['Occupancy trend (day)'], $rows);
        self::assertContains(['Sep 1', '25.0%', '1', '4'], $rows);
        self::assertContains(['Sep 2', '50.0%', '2', '4'], $rows);
        self::assertContains(['Live operational snapshot'], $rows);
        self::assertContains(['Snapshot at', '2026-09-04 12:34:56 UTC'], $rows);
        self::assertContains(['Conference rooms available now', '2'], $rows);
        self::assertContains(['Tables reserved or occupied now', '3'], $rows);
    }

    public function test_authorized_staff_downloads_the_applied_range_in_a_date_specific_csv(): void
    {
        $this->travelTo('2026-09-04 12:34:56');
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($admin);

        $page = new OccupancyReport;
        $page->period = 'custom';
        $page->startDate = '2026-09-01';
        $page->endDate = '2026-09-04';
        $page->draftStartDate = '2025-01-01';
        $page->draftEndDate = '2025-01-31';

        $response = $page->exportCsv();

        self::assertInstanceOf(StreamedResponse::class, $response);
        self::assertSame('text/csv; charset=UTF-8', $response->headers->get('content-type'));
        self::assertStringContainsString(
            'occupancy-report-2026-09-01-to-2026-09-04.csv',
            (string) $response->headers->get('content-disposition'),
        );

        ob_start();
        $response->sendContent();
        $rows = $this->csvRows((string) ob_get_clean());

        self::assertContains(['Start date', '2026-09-01'], $rows);
        self::assertContains(['End date', '2026-09-04'], $rows);
        self::assertNotContains(['Start date', '2025-01-01'], $rows);
    }

    public function test_occupancy_page_displays_the_csv_export_action_to_authorized_staff(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        $this->actingAs($admin)
            ->get('/admin/occupancy-report')
            ->assertOk()
            ->assertSee('Export CSV');
    }

    public function test_staff_without_occupancy_access_cannot_invoke_the_export_method(): void
    {
        $accountant = User::factory()->create();
        $accountant->assignRole(Role::findOrCreate('accountant', 'web'));
        $this->actingAs($accountant);

        try {
            (new OccupancyReport)->exportCsv();
            self::fail('The occupancy export should reject staff without report access.');
        } catch (HttpException $exception) {
            self::assertSame(403, $exception->getStatusCode());
        }
    }

    /**
     * @return list<list<string|null>>
     */
    private function csvRows(string $csv): array
    {
        $stream = fopen('php://temp', 'r+');
        self::assertIsResource($stream);
        fwrite($stream, $csv);
        rewind($stream);

        $rows = [];

        while (($row = fgetcsv($stream, escape: '\\')) !== false) {
            $rows[] = $row;
        }

        fclose($stream);

        return $rows;
    }
}
