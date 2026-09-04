<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\RevenueReport;
use App\Models\User;
use App\Support\Reporting\RevenueReportCsv;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RevenueReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_contains_each_visible_revenue_report_section(): void
    {
        if (! class_exists(RevenueReportCsv::class)) {
            self::fail('The revenue report CSV serializer has not been implemented.');
        }

        $report = [
            'periodStart' => Carbon::parse('2026-09-01')->startOfDay(),
            'periodEnd' => Carbon::parse('2026-09-02')->endOfDay(),
            'generatedAt' => Carbon::parse('2026-09-04 12:34:56', 'UTC'),
            'revenue' => 1000.0,
            'paymentsReceived' => 5,
            'refunds' => 100.0,
            'refundCount' => 1,
            'netRevenue' => 900.0,
            'outstanding' => 300.0,
            'revenueByChannel' => [
                'hotel' => ['total' => 600.0, 'payment_count' => 3],
                'conference' => ['total' => 200.0, 'payment_count' => 1],
                'table' => ['total' => 0.0, 'payment_count' => 0],
                'food' => ['total' => 200.0, 'payment_count' => 1],
                'other' => ['total' => 0.0, 'payment_count' => 0],
            ],
            'comparison' => [
                'previousPeriodLabel' => 'Aug 30, 2026 to Aug 31, 2026',
                'revenue' => ['current' => 1000.0, 'previous' => 800.0, 'difference' => 200.0, 'percentageChange' => 25.0],
                'refunds' => ['current' => 100.0, 'previous' => 50.0, 'difference' => 50.0, 'percentageChange' => 100.0],
                'netRevenue' => ['current' => 900.0, 'previous' => 750.0, 'difference' => 150.0, 'percentageChange' => 20.0],
            ],
            'trend' => [
                'granularity' => 'day',
                'labels' => ['Sep 1', 'Sep 2'],
                'collected' => [400.0, 600.0],
                'refunds' => [0.0, 100.0],
                'net' => [400.0, 500.0],
            ],
            'outstandingBreakdown' => [
                'hotel' => 100.0,
                'conference' => 200.0,
                'table' => 0.0,
                'food' => 0.0,
            ],
            'methods' => new Collection([
                (object) ['method' => 'cash', 'total' => 800.0, 'payment_count' => 4],
                (object) ['method' => 'momo', 'total' => 200.0, 'payment_count' => 1],
            ]),
        ];

        $rows = $this->csvRows((new RevenueReportCsv)->toCsv($report, 'Sep 1, 2026 to Sep 2, 2026'));

        self::assertContains(['Revenue Report'], $rows);
        self::assertContains(['Period', 'Sep 1, 2026 to Sep 2, 2026'], $rows);
        self::assertContains(['Start date', '2026-09-01'], $rows);
        self::assertContains(['End date', '2026-09-02'], $rows);
        self::assertContains(['Generated at', '2026-09-04 12:34:56 UTC'], $rows);
        self::assertContains(['Revenue received', '1000.00', '5 payments'], $rows);
        self::assertContains(['Refunds processed', '100.00', '1 refund'], $rows);
        self::assertContains(['Net revenue', '900.00'], $rows);
        self::assertContains(['Outstanding balance', '300.00'], $rows);
        self::assertContains(['Hotel bookings', '3', '600.00', '60.0%'], $rows);
        self::assertContains(['Revenue received', '1000.00', '800.00', '200.00', '25.0%'], $rows);
        self::assertContains(['Sep 2', '600.00', '100.00', '500.00'], $rows);
        self::assertContains(['Conference bookings', '200.00', '66.7%'], $rows);
        self::assertContains(['Cash', '4', '800.00', '80.0%'], $rows);
        self::assertContains(['Mobile money', '1', '200.00', '20.0%'], $rows);
    }

    public function test_authorized_staff_downloads_the_applied_revenue_range(): void
    {
        self::assertTrue(
            method_exists(RevenueReport::class, 'exportCsv'),
            'The revenue report CSV download has not been implemented.',
        );

        $this->travelTo('2026-09-04 12:34:56');
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($admin);

        $page = new RevenueReport;
        $page->period = 'custom';
        $page->startDate = '2026-09-01';
        $page->endDate = '2026-09-04';
        $page->draftStartDate = '2025-01-01';
        $page->draftEndDate = '2025-01-31';

        $response = $page->exportCsv();

        self::assertInstanceOf(StreamedResponse::class, $response);
        self::assertSame('text/csv; charset=UTF-8', $response->headers->get('content-type'));
        self::assertStringContainsString(
            'revenue-report-2026-09-01-to-2026-09-04.csv',
            (string) $response->headers->get('content-disposition'),
        );

        ob_start();
        $response->sendContent();
        $rows = $this->csvRows((string) ob_get_clean());

        self::assertContains(['Start date', '2026-09-01'], $rows);
        self::assertContains(['End date', '2026-09-04'], $rows);
        self::assertNotContains(['Start date', '2025-01-01'], $rows);
    }

    public function test_revenue_page_exposes_csv_and_client_side_print_actions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        $response = $this->actingAs($admin)->get('/admin/revenue-report');

        $response->assertOk()
            ->assertSee('Export CSV')
            ->assertSee('Print report')
            ->assertSee('window.print()', false)
            ->assertSee('data-revenue-report-page', false)
            ->assertSee('@media print', false);
    }

    public function test_staff_without_revenue_report_access_cannot_export_it(): void
    {
        self::assertTrue(
            method_exists(RevenueReport::class, 'exportCsv'),
            'The revenue report CSV download has not been implemented.',
        );

        $receptionist = User::factory()->create();
        $receptionist->assignRole(Role::findOrCreate('receptionist', 'web'));
        $this->actingAs($receptionist);

        try {
            (new RevenueReport)->exportCsv();
            self::fail('The revenue export should reject staff without report access.');
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
