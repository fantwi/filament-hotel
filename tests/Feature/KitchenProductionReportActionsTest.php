<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\KitchenProductionReport;
use App\Filament\Admin\Resources\KitchenProductions\Pages\ListKitchenProductions;
use App\Filament\Admin\Resources\RestaurantOrders\Pages\ListRestaurantOrders;
use App\Models\KitchenProduction;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\RestaurantOrder;
use App\Models\User;
use App\Support\Reporting\KitchenProductionReportCsv;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class KitchenProductionReportActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_exception_filters_select_only_rows_that_need_the_requested_attention(): void
    {
        $this->travelTo('2026-09-05 09:00:00');
        $category = $this->category();
        $healthy = $this->menuItem($category, 'Healthy Meal', 'healthy-meal', threshold: 5);
        $low = $this->menuItem($category, 'Low Meal', 'low-meal', threshold: 5);
        $negative = $this->menuItem($category, 'Negative Meal', 'negative-meal', threshold: 0);
        $wasted = $this->menuItem($category, 'Wasted Meal', 'wasted-meal', threshold: 1);
        $this->production($healthy, produced: 20);
        $this->production($low, produced: 4);
        $this->production($negative, produced: 2);
        $this->sale($negative, quantity: 4);
        $this->production($wasted, produced: 10, wasted: 2);
        $page = $this->reportPage();

        $page->exceptionFilter = 'attention';
        self::assertSame(
            [$low->name, $negative->name],
            $page->getReportProperty()['rows']->pluck('name')->all(),
        );

        $page->exceptionFilter = 'negative_variance';
        self::assertSame(
            [$negative->name],
            $page->getReportProperty()['rows']->pluck('name')->all(),
        );

        $page->exceptionFilter = 'wastage';
        self::assertSame(
            [$wasted->name],
            $page->getReportProperty()['rows']->pluck('name')->all(),
        );
    }

    public function test_drill_down_urls_open_only_the_contributing_batches_and_orders(): void
    {
        $this->travelTo('2026-09-05 09:00:00');
        $user = $this->reportUser(manageDestinations: true);
        $this->actingAs($user);
        $category = $this->category();
        $target = $this->menuItem($category, 'Target Meal', 'target-meal');
        $other = $this->menuItem($category, 'Other Meal', 'other-meal');
        $targetBatch = $this->production($target, produced: 10, date: '2026-09-03');
        $outsideBatch = $this->production($target, produced: 5, date: '2026-08-31');
        $otherBatch = $this->production($other, produced: 8, date: '2026-09-03');
        $targetOrder = $this->sale($target, quantity: 2, deductedAt: '2026-09-04 12:00:00');
        $outsideOrder = $this->sale($target, quantity: 1, deductedAt: '2026-08-31 12:00:00');
        $otherOrder = $this->sale($other, quantity: 1, deductedAt: '2026-09-04 12:00:00');
        $page = $this->reportPage();
        $row = $page->getReportProperty()['rows']->firstWhere('name', $target->name);

        $batchQuery = $this->urlQuery($page->productionBatchesUrl($row));
        self::assertSame((string) $target->getKey(), (string) $batchQuery['filters']['menu_item']['value']);
        self::assertSame('2026-09-01', $batchQuery['filters']['production_date']['from']);
        self::assertSame('2026-09-30', $batchQuery['filters']['production_date']['until']);

        Livewire::withQueryParams($batchQuery)
            ->actingAs($user)
            ->test(ListKitchenProductions::class)
            ->assertCanSeeTableRecords([$targetBatch])
            ->assertCanNotSeeTableRecords([$outsideBatch, $otherBatch]);

        $orderQuery = $this->urlQuery($page->restaurantOrdersUrl($row));
        self::assertSame((string) $target->getKey(), (string) $orderQuery['filters']['menu_item']['value']);
        self::assertSame('2026-09-01', $orderQuery['filters']['stock_movement_at']['from']);
        self::assertSame('2026-09-30', $orderQuery['filters']['stock_movement_at']['until']);

        Livewire::withQueryParams($orderQuery)
            ->actingAs($user)
            ->test(ListRestaurantOrders::class)
            ->assertCanSeeTableRecords([$targetOrder])
            ->assertCanNotSeeTableRecords([$outsideOrder, $otherOrder]);

        $html = Livewire::actingAs($user)
            ->test(KitchenProductionReport::class)
            ->assertSuccessful()
            ->html();

        self::assertStringContainsString('data-kitchen-production-batches-link', $html);
        self::assertStringContainsString('data-kitchen-production-orders-link', $html);
    }

    public function test_report_only_staff_do_not_receive_operational_drill_down_links(): void
    {
        $this->travelTo('2026-09-05 09:00:00');
        $user = $this->reportUser();
        $item = $this->menuItem($this->category(), 'View-only Meal', 'view-only-meal');
        $this->production($item, produced: 10);

        $html = Livewire::actingAs($user)
            ->test(KitchenProductionReport::class)
            ->assertSuccessful()
            ->html();

        self::assertStringNotContainsString('data-kitchen-production-batches-link', $html);
        self::assertStringNotContainsString('data-kitchen-production-orders-link', $html);
    }

    public function test_csv_contains_filtered_report_rows_and_neutralizes_spreadsheet_formulas(): void
    {
        self::assertTrue(
            class_exists(KitchenProductionReportCsv::class),
            'The kitchen production report CSV serializer has not been implemented.',
        );

        $report = [
            'periodStart' => Carbon::parse('2026-09-01')->startOfDay(),
            'periodEnd' => Carbon::parse('2026-09-30')->endOfDay(),
            'generatedAt' => Carbon::parse('2026-10-01 08:30:00', 'UTC'),
            'summary' => [
                'tracked_items' => 3,
                'healthy_items' => 1,
                'low_stock_items' => 2,
                'negative_variance_items' => 1,
                'collected_revenue' => 500.50,
                'refunded_revenue' => 25,
                'net_revenue' => 475.50,
            ],
            'rows' => new Collection([
                [
                    'name' => '=SUM(1,1)',
                    'category' => '+Prepared meals',
                    'unit' => 'portion',
                    'usage_per_sale' => 1,
                    'produced' => 10,
                    'wasted' => 1,
                    'net_produced' => 9,
                    'sold_units' => 4,
                    'production_amount_sold' => 4,
                    'opening_balance' => 2,
                    'period_variance' => 5,
                    'closing_balance' => 7,
                    'sell_through' => 36.3636,
                    'collected_revenue' => 200.50,
                    'refunded_revenue' => 25,
                    'net_revenue' => 175.50,
                    'stock_status' => 'healthy',
                ],
            ]),
        ];

        $rows = $this->csvRows((new KitchenProductionReportCsv)->toCsv(
            $report,
            'Sep 1, 2026 - Sep 30, 2026',
        ));

        self::assertContains(['Kitchen Production vs Sales Report'], $rows);
        self::assertContains(['Period', 'Sep 1, 2026 - Sep 30, 2026'], $rows);
        self::assertContains(['Tracked menu items', '3'], $rows);
        self::assertContains(['Tracked-item net revenue (GHS)', '475.50'], $rows);
        self::assertContains([
            "'=SUM(1,1)",
            "'+Prepared meals",
            'portion',
            '1.000',
            '10.000',
            '1.000',
            '9.000',
            '4',
            '4.000',
            '2.000',
            '5.000',
            '7.000',
            '36.4%',
            '200.50',
            '25.00',
            '175.50',
            'Healthy',
        ], $rows);
    }

    public function test_authorized_export_uses_applied_filters_without_truncating_to_the_current_page(): void
    {
        self::assertTrue(
            method_exists(KitchenProductionReport::class, 'exportCsv'),
            'The kitchen production report CSV download has not been implemented.',
        );

        $this->travelTo('2026-09-05 09:00:00');
        $this->actingAs($this->reportUser());
        $category = $this->category();

        foreach (range(1, 11) as $number) {
            $item = $this->menuItem(
                $category,
                sprintf('Wasted Meal %02d', $number),
                sprintf('wasted-meal-%02d', $number),
            );
            $this->production($item, produced: 5, wasted: 1);
        }

        $excluded = $this->menuItem($category, 'Healthy Meal', 'healthy-export-meal');
        $this->production($excluded, produced: 10);
        $page = $this->reportPage();
        $page->exceptionFilter = 'wastage';
        $page->perPage = 10;

        $response = $page->exportCsv();

        self::assertInstanceOf(StreamedResponse::class, $response);
        self::assertStringContainsString(
            'kitchen-production-report-2026-09-01-to-2026-09-30.csv',
            (string) $response->headers->get('content-disposition'),
        );
        ob_start();
        $response->sendContent();
        $csv = (string) ob_get_clean();

        self::assertSame(11, substr_count($csv, 'Wasted Meal'));
        self::assertStringNotContainsString('Healthy Meal', $csv);
    }

    public function test_page_exposes_export_and_print_actions_with_a_print_friendly_layout(): void
    {
        $user = $this->reportUser();

        $this->actingAs($user)
            ->get(KitchenProductionReport::getUrl())
            ->assertOk()
            ->assertSee('Export CSV')
            ->assertSee('Print report')
            ->assertSee('window.print()', false)
            ->assertSee('data-kitchen-production-report-page', false)
            ->assertSee('@media print', false);
    }

    public function test_staff_without_report_access_cannot_export(): void
    {
        self::assertTrue(
            method_exists(KitchenProductionReport::class, 'exportCsv'),
            'The kitchen production report CSV download has not been implemented.',
        );
        $this->actingAs(User::factory()->create());

        try {
            (new KitchenProductionReport)->exportCsv();
            self::fail('The kitchen production report export should reject unauthorized staff.');
        } catch (HttpException $exception) {
            self::assertSame(403, $exception->getStatusCode());
        }
    }

    private function reportPage(): KitchenProductionReport
    {
        $page = new KitchenProductionReport;
        $page->period = 'custom';
        $page->startDate = '2026-09-01';
        $page->endDate = '2026-09-30';

        return $page;
    }

    private function reportUser(bool $manageDestinations = false): User
    {
        $user = User::factory()->create(['department' => 'kitchen']);
        $user->givePermissionTo(Permission::findOrCreate('view kitchen production reports', 'web'));

        if ($manageDestinations) {
            $user->givePermissionTo(Permission::findOrCreate('manage kitchen production', 'web'));
            $user->givePermissionTo(Permission::findOrCreate('manage kitchen orders', 'web'));
        }

        return $user;
    }

    private function category(): MenuCategory
    {
        return MenuCategory::query()->create([
            'name' => 'Report actions',
            'slug' => 'report-actions-'.str()->lower(str()->random(8)),
        ]);
    }

    private function menuItem(
        MenuCategory $category,
        string $name,
        string $slug,
        float $threshold = 5,
    ): MenuItem {
        return MenuItem::query()->create([
            'menu_category_id' => $category->getKey(),
            'name' => $name,
            'slug' => $slug,
            'price' => 25,
            'tracks_kitchen_production' => true,
            'production_unit' => 'portion',
            'production_usage_per_sale' => 1,
            'low_stock_threshold' => $threshold,
        ]);
    }

    private function production(
        MenuItem $item,
        float $produced,
        float $wasted = 0,
        string $date = '2026-09-03',
    ): KitchenProduction {
        return KitchenProduction::query()->create([
            'menu_item_id' => $item->getKey(),
            'production_date' => $date,
            'quantity_produced' => $produced,
            'quantity_wasted' => $wasted,
        ]);
    }

    private function sale(
        MenuItem $item,
        int $quantity,
        string $deductedAt = '2026-09-04 12:00:00',
    ): RestaurantOrder {
        $order = RestaurantOrder::query()->create([
            'order_number' => 'REPORT-ACTION-'.str()->upper(str()->random(10)),
            'ordering_channel' => 'web',
            'subtotal' => $quantity * 25,
            'total' => $quantity * 25,
            'status' => 'served',
            'payment_status' => 'completed',
            'stock_deducted_at' => $deductedAt,
        ]);
        $order->items()->create([
            'menu_item_id' => $item->getKey(),
            'item_name' => $item->name,
            'production_unit' => 'portion',
            'production_usage_per_sale' => 1,
            'quantity' => $quantity,
            'unit_price' => 25,
            'total_price' => $quantity * 25,
        ]);

        return $order;
    }

    /**
     * @return array<string, mixed>
     */
    private function urlQuery(?string $url): array
    {
        self::assertNotNull($url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return $query;
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
