<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\RestaurantOrderReport;
use App\Models\Guest;
use App\Models\RestaurantOrder;
use App\Models\User;
use App\Support\Reporting\RestaurantOrderReportCsv;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RestaurantOrderReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_contains_the_complete_restaurant_report_and_neutralizes_spreadsheet_formulas(): void
    {
        self::assertTrue(
            class_exists(RestaurantOrderReportCsv::class),
            'The restaurant report CSV serializer has not been implemented.',
        );

        $report = [
            'periodStart' => Carbon::parse('2026-09-01')->startOfDay(),
            'periodEnd' => Carbon::parse('2026-09-02')->endOfDay(),
            'generatedAt' => Carbon::parse('2026-09-04 12:34:56', 'UTC'),
            'totalOrders' => 4,
            'totalItems' => 11,
            'paidOrders' => 2,
            'pendingOrders' => 1,
            'cancelledOrders' => 1,
            'activeOrders' => 1,
            'liveKitchenOrders' => 3,
            'revenue' => 750.50,
            'refunds' => 50.0,
            'netRevenue' => 700.50,
            'outstanding' => 125.25,
            'averageOrderValue' => 375.25,
            'paymentRate' => 66.7,
            'comparison' => [
                'previousPeriodLabel' => 'Aug 30, 2026 to Aug 31, 2026',
                'orders' => ['current' => 4, 'previous' => 2, 'difference' => 2, 'percentageChange' => 100.0],
                'items' => ['current' => 11, 'previous' => 8, 'difference' => 3, 'percentageChange' => 37.5],
                'netRevenue' => ['current' => 700.50, 'previous' => 500.0, 'difference' => 200.50, 'percentageChange' => 40.1],
            ],
            'trend' => [
                'granularity' => 'day',
                'labels' => ['Sep 1', 'Sep 2'],
                'orders' => [1, 3],
                'items' => [3, 8],
                'collected' => [250.0, 500.50],
                'refunds' => [0.0, 50.0],
                'netRevenue' => [250.0, 450.50],
            ],
            'orders' => new Collection([
                (object) [
                    'order_number' => '=SUM(1,1)',
                    'customer_email' => '+walkin@example.test',
                    'ordering_channel' => 'qr',
                    'status' => 'ready',
                    'payment_status' => 'completed',
                    'items_sum_quantity' => 5,
                    'total' => 220.50,
                    'created_at' => Carbon::parse('2026-09-02 08:15:00'),
                    'guest' => (object) [
                        'full_name' => '-Formula Guest',
                        'email' => '@guest.example.test',
                    ],
                ],
            ]),
        ];

        $rows = $this->csvRows((new RestaurantOrderReportCsv)->toCsv(
            $report,
            'Sep 1, 2026 to Sep 2, 2026',
        ));

        self::assertContains(['Restaurant Order Report'], $rows);
        self::assertContains(['Period', 'Sep 1, 2026 to Sep 2, 2026'], $rows);
        self::assertContains(['Start date', '2026-09-01'], $rows);
        self::assertContains(['End date', '2026-09-02'], $rows);
        self::assertContains(['Generated at', '2026-09-04 12:34:56 UTC'], $rows);
        self::assertContains(['Orders received', '4', ''], $rows);
        self::assertContains(['Items ordered', '11', ''], $rows);
        self::assertContains(['Paid orders', '2', ''], $rows);
        self::assertContains(['Awaiting payment', '1', ''], $rows);
        self::assertContains(['Cancelled orders', '1', ''], $rows);
        self::assertContains(['Live kitchen queue (current)', '3', 'Not limited to the selected period'], $rows);
        self::assertContains(['Gross collections (GHS)', '750.50', ''], $rows);
        self::assertContains(['Refunds processed (GHS)', '50.00', ''], $rows);
        self::assertContains(['Net revenue (GHS)', '700.50', ''], $rows);
        self::assertContains(['Outstanding balance (GHS)', '125.25', ''], $rows);
        self::assertContains(['Payment rate', '66.7%', ''], $rows);
        self::assertContains(['Orders received', '4', '2', '2', '100.0%'], $rows);
        self::assertContains(['Net revenue (GHS)', '700.50', '500.00', '200.50', '40.1%'], $rows);
        self::assertContains(['Sep 2', '3', '8', '500.50', '50.00', '450.50'], $rows);
        self::assertContains([
            "'=SUM(1,1)",
            "'-Formula Guest",
            "'@guest.example.test",
            'Table QR',
            'Ready',
            'Completed',
            '5',
            '220.50',
            '2026-09-02 08:15:00',
        ], $rows);
        self::assertNotContains([
            '=SUM(1,1)',
            '-Formula Guest',
            '@guest.example.test',
            'Table QR',
            'Ready',
            'Completed',
            '5',
            '220.50',
            '2026-09-02 08:15:00',
        ], $rows);
    }

    public function test_authorized_staff_exports_every_order_in_the_applied_period(): void
    {
        self::assertTrue(
            method_exists(RestaurantOrderReport::class, 'exportCsv'),
            'The restaurant report CSV download has not been implemented.',
        );

        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($admin);

        $guest = Guest::query()->create([
            'first_name' => 'Akosua',
            'last_name' => 'Mensah',
            'email' => 'akosua@example.test',
        ]);

        $this->travelTo('2026-09-01 10:00:00');
        RestaurantOrder::query()->create([
            'guest_id' => $guest->id,
            'order_number' => 'FOOD-IN-RANGE-FIRST',
            'ordering_channel' => 'web',
            'subtotal' => 80,
            'total' => 80,
            'status' => 'served',
            'payment_status' => 'completed',
        ]);

        $this->travelTo('2026-09-04 12:00:00');
        RestaurantOrder::query()->create([
            'order_number' => 'FOOD-IN-RANGE-SECOND',
            'customer_email' => 'walkin@example.test',
            'ordering_channel' => 'staff',
            'subtotal' => 45,
            'total' => 45,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);

        $this->travelTo('2026-08-31 23:59:59');
        RestaurantOrder::query()->create([
            'order_number' => 'FOOD-OUTSIDE-RANGE',
            'subtotal' => 30,
            'total' => 30,
            'status' => 'served',
            'payment_status' => 'completed',
        ]);

        $this->travelTo('2026-09-05 09:00:00');
        $page = new RestaurantOrderReport;
        $page->period = 'custom';
        $page->startDate = '2026-09-01';
        $page->endDate = '2026-09-04';
        $page->draftStartDate = '2025-01-01';
        $page->draftEndDate = '2025-01-31';
        $page->registerSearch = 'SECOND';
        $page->perPage = 10;

        $response = $page->exportCsv();

        self::assertInstanceOf(StreamedResponse::class, $response);
        self::assertSame('text/csv; charset=UTF-8', $response->headers->get('content-type'));
        self::assertStringContainsString(
            'restaurant-order-report-2026-09-01-to-2026-09-04.csv',
            (string) $response->headers->get('content-disposition'),
        );

        ob_start();
        $response->sendContent();
        $csv = (string) ob_get_clean();
        $rows = $this->csvRows($csv);

        self::assertContains(['Start date', '2026-09-01'], $rows);
        self::assertContains(['End date', '2026-09-04'], $rows);
        self::assertStringContainsString('FOOD-IN-RANGE-FIRST', $csv);
        self::assertStringContainsString('FOOD-IN-RANGE-SECOND', $csv);
        self::assertStringNotContainsString('FOOD-OUTSIDE-RANGE', $csv);
        self::assertStringNotContainsString('2025-01-01', $csv);
    }

    public function test_restaurant_report_page_exposes_csv_and_client_side_print_actions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        $response = $this->actingAs($admin)->get('/admin/restaurant-order-report');

        $response->assertOk()
            ->assertSee('Export CSV')
            ->assertSee('Print report')
            ->assertSee('window.print()', false)
            ->assertSee('data-restaurant-report-page', false)
            ->assertSee('data-restaurant-report-controls', false)
            ->assertSee('data-restaurant-report-loading-overlay', false)
            ->assertSee('@media print', false);
    }

    public function test_staff_without_restaurant_report_access_cannot_export_it(): void
    {
        self::assertTrue(
            method_exists(RestaurantOrderReport::class, 'exportCsv'),
            'The restaurant report CSV download has not been implemented.',
        );

        $receptionist = User::factory()->create();
        $receptionist->assignRole(Role::findOrCreate('receptionist', 'web'));
        $this->actingAs($receptionist);

        try {
            (new RestaurantOrderReport)->exportCsv();
            self::fail('The restaurant report export should reject staff without report access.');
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
