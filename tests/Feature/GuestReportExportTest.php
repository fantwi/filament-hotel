<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\GuestReport;
use App\Models\User;
use App\Support\Reporting\GuestReportCsv;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class GuestReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_contains_every_guest_report_section_and_neutralizes_spreadsheet_formulas(): void
    {
        self::assertTrue(
            class_exists(GuestReportCsv::class),
            'The guest report CSV serializer has not been implemented.',
        );

        $report = [
            'periodStart' => Carbon::parse('2026-09-01')->startOfDay(),
            'periodEnd' => Carbon::parse('2026-09-02')->endOfDay(),
            'generatedAt' => Carbon::parse('2026-09-04 12:34:56', 'UTC'),
            'totalGuests' => 10,
            'newGuests' => 3,
            'payingGuests' => 2,
            'returningGuests' => 1,
            'averageSpend' => 375.25,
            'totalPaid' => 750.50,
            'refundTotal' => 50.0,
            'refundCount' => 1,
            'netSpend' => 700.50,
            'paymentCount' => 4,
            'activity' => [
                'hotel' => 2,
                'conference' => 1,
                'table' => 0,
                'food' => 1,
                'other' => 0,
            ],
            'comparison' => [
                'previousPeriodLabel' => 'Aug 30, 2026 to Aug 31, 2026',
                'newGuests' => ['current' => 3, 'previous' => 2, 'difference' => 1, 'percentageChange' => 50.0],
                'payingGuests' => ['current' => 2, 'previous' => 1, 'difference' => 1, 'percentageChange' => 100.0],
                'returningGuests' => ['current' => 1, 'previous' => 0, 'difference' => 1, 'percentageChange' => null],
            ],
            'trend' => [
                'granularity' => 'day',
                'labels' => ['Sep 1', 'Sep 2'],
                'newGuests' => [1, 2],
                'payingGuests' => [1, 1],
                'returningGuests' => [0, 1],
            ],
            'topGuests' => new Collection([
                (object) [
                    'guest' => (object) [
                        'full_name' => '=SUM(1,1)',
                        'email' => '+danger@example.test',
                    ],
                    'payment_count' => 3,
                    'total_spend' => 500.25,
                ],
            ]),
        ];

        $rows = $this->csvRows((new GuestReportCsv)->toCsv($report, 'Sep 1, 2026 to Sep 2, 2026'));

        self::assertContains(['Guest Report'], $rows);
        self::assertContains(['Period', 'Sep 1, 2026 to Sep 2, 2026'], $rows);
        self::assertContains(['Start date', '2026-09-01'], $rows);
        self::assertContains(['End date', '2026-09-02'], $rows);
        self::assertContains(['Generated at', '2026-09-04 12:34:56 UTC'], $rows);
        self::assertContains(['All-time guest profiles', '10', ''], $rows);
        self::assertContains(['New guest profiles', '3', ''], $rows);
        self::assertContains(['Guests with collected payments', '2', ''], $rows);
        self::assertContains(['Repeat-service guests', '1', ''], $rows);
        self::assertContains(['Average collected per paying guest (GHS)', '375.25', ''], $rows);
        self::assertContains(['Gross guest revenue (GHS)', '750.50', '4 payments'], $rows);
        self::assertContains(['Refunds processed (GHS)', '50.00', '1 refund'], $rows);
        self::assertContains(['Net guest revenue (GHS)', '700.50', ''], $rows);
        self::assertContains(['Hotel bookings', '2', '50.0%'], $rows);
        self::assertContains(['Food orders', '1', '25.0%'], $rows);
        self::assertContains(['New guest profiles', '3', '2', '1', '50.0%'], $rows);
        self::assertContains(['Repeat-service guests', '1', '0', '1', 'N/A'], $rows);
        self::assertContains(['Sep 2', '2', '1', '1'], $rows);
        self::assertContains(["'=SUM(1,1)", "'+danger@example.test", '3', '500.25'], $rows);
        self::assertNotContains(['=SUM(1,1)', '+danger@example.test', '3', '500.25'], $rows);
    }

    public function test_authorized_staff_downloads_the_applied_guest_report_range(): void
    {
        self::assertTrue(
            method_exists(GuestReport::class, 'exportCsv'),
            'The guest report CSV download has not been implemented.',
        );

        $this->travelTo('2026-09-04 12:34:56');
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($admin);

        $page = new GuestReport;
        $page->period = 'custom';
        $page->startDate = '2026-09-01';
        $page->endDate = '2026-09-04';
        $page->draftStartDate = '2025-01-01';
        $page->draftEndDate = '2025-01-31';

        $response = $page->exportCsv();

        self::assertInstanceOf(StreamedResponse::class, $response);
        self::assertSame('text/csv; charset=UTF-8', $response->headers->get('content-type'));
        self::assertStringContainsString(
            'guest-report-2026-09-01-to-2026-09-04.csv',
            (string) $response->headers->get('content-disposition'),
        );

        ob_start();
        $response->sendContent();
        $rows = $this->csvRows((string) ob_get_clean());

        self::assertContains(['Start date', '2026-09-01'], $rows);
        self::assertContains(['End date', '2026-09-04'], $rows);
        self::assertNotContains(['Start date', '2025-01-01'], $rows);
    }

    public function test_guest_report_page_exposes_csv_and_client_side_print_actions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        $response = $this->actingAs($admin)->get('/admin/guest-report');

        $response->assertOk()
            ->assertSee('Export CSV')
            ->assertSee('Print report')
            ->assertSee('window.print()', false)
            ->assertSee('data-guest-report-page', false)
            ->assertSee('data-guest-loading-overlay', false)
            ->assertSee('@media print', false);
    }

    public function test_staff_without_guest_report_access_cannot_export_it(): void
    {
        self::assertTrue(
            method_exists(GuestReport::class, 'exportCsv'),
            'The guest report CSV download has not been implemented.',
        );

        $receptionist = User::factory()->create();
        $receptionist->assignRole(Role::findOrCreate('receptionist', 'web'));
        $this->actingAs($receptionist);

        try {
            (new GuestReport)->exportCsv();
            self::fail('The guest export should reject staff without report access.');
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
