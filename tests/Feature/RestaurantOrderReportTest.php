<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\RestaurantOrderReport;
use App\Filament\Admin\Resources\RestaurantOrders\RestaurantOrderResource;
use App\Filament\Admin\Widgets\RestaurantOrderReportStats;
use App\Models\Guest;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RestaurantOrderReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_report_provides_structured_summary_data_for_the_selected_period(): void
    {
        $reportPage = new RestaurantOrderReport;
        $reportPage->period = 'weekly';

        self::assertSame('Weekly', $reportPage->periodLabel());

        $report = $reportPage->getReportData();

        self::assertArrayHasKey('totalItems', $report);
        self::assertArrayHasKey('activeOrders', $report);
        self::assertArrayHasKey('paymentRate', $report);
        self::assertSame(0, $report['totalOrders']);
    }

    public function test_live_kitchen_queue_is_current_and_independent_of_the_report_period(): void
    {
        $this->travelTo('2026-07-10 09:00:00');
        RestaurantOrder::query()->create([
            'order_number' => 'FOOD-OLDER-ACTIVE',
            'subtotal' => 100,
            'total' => 100,
            'status' => 'confirmed',
            'payment_status' => 'completed',
        ]);
        RestaurantOrder::query()->create([
            'order_number' => 'FOOD-OLDER-SERVED',
            'subtotal' => 80,
            'total' => 80,
            'status' => 'served',
            'payment_status' => 'completed',
        ]);

        $this->travelTo('2026-08-10 09:00:00');
        RestaurantOrder::query()->create([
            'order_number' => 'FOOD-PERIOD-ACTIVE',
            'subtotal' => 120,
            'total' => 120,
            'status' => 'preparing',
            'payment_status' => 'completed',
        ]);

        $reportPage = new RestaurantOrderReport;
        $reportPage->period = 'custom';
        $reportPage->startDate = '2026-08-01';
        $reportPage->endDate = '2026-08-31';
        $metrics = $reportPage->getReportMetrics();

        self::assertArrayHasKey('liveKitchenOrders', $metrics);
        self::assertSame(1, $metrics['totalOrders']);
        self::assertSame(1, $metrics['activeOrders']);
        self::assertSame(2, $metrics['liveKitchenOrders']);
    }

    public function test_restaurant_report_stats_widget_uses_precomputed_data_without_queries(): void
    {
        self::assertTrue(is_subclass_of(RestaurantOrderReportStats::class, StatsOverviewWidget::class));

        $widget = new RestaurantOrderReportStats;
        $widget->reportData = [
            'totalOrders' => 12,
            'totalItems' => 30,
            'revenue' => 1250.0,
            'refunds' => 100.0,
            'netRevenue' => 1150.0,
            'outstanding' => 450.0,
            'averageOrderValue' => 625.0,
            'paymentRate' => 75.0,
        ];
        $widget->reportPeriodLabel = 'Quarterly';
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $stats = $method->invoke($widget);
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }

        self::assertCount(0, $queries, 'The stats widget should not repeat the report database queries.');
        self::assertCount(4, $stats);
        self::assertSame('Orders received', $stats[0]->getLabel());
        self::assertSame('12', $stats[0]->getValue());
        self::assertSame('30 items in Quarterly', $stats[0]->getDescription());
        self::assertSame('Net revenue', $stats[1]->getLabel());
        self::assertSame('GHS 1,150.00', $stats[1]->getValue());
        self::assertSame('GHS 1,250.00 collected · GHS 100.00 refunded', $stats[1]->getDescription());
        self::assertSame('GHS 450.00', $stats[2]->getValue());
        self::assertSame('Remaining balance on open orders', $stats[2]->getDescription());
        self::assertSame('Average collected order', $stats[3]->getLabel());
        self::assertSame('GHS 625.00', $stats[3]->getValue());
        self::assertSame('75.0% payment completion', $stats[3]->getDescription());

        $widget->reportData['totalItems'] = 1;
        $singleItemStats = $method->invoke($widget);

        self::assertSame('1 item in Quarterly', $singleItemStats[0]->getDescription());
    }

    public function test_restaurant_order_register_uses_server_side_pagination(): void
    {
        $reportPage = new RestaurantOrderReport;
        $reportPage->period = 'monthly';
        $reportPage->perPage = 10;

        $report = $reportPage->getReportData();

        self::assertInstanceOf(LengthAwarePaginator::class, $report['orders']);
        self::assertSame(0, $report['orders']->total());
    }

    public function test_restaurant_order_register_aggregates_item_quantities_without_loading_item_models(): void
    {
        $this->travelTo('2026-09-05 09:00:00');
        $guest = Guest::query()->create([
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'email' => 'ama@example.test',
        ]);
        $menuItem = $this->menuItem('aggregated-items');
        $order = RestaurantOrder::query()->create([
            'guest_id' => $guest->id,
            'order_number' => 'FOOD-AGGREGATED-ITEMS',
            'ordering_channel' => 'web',
            'subtotal' => 200,
            'total' => 200,
            'status' => 'confirmed',
            'payment_status' => 'completed',
        ]);

        foreach ([2, 3] as $quantity) {
            RestaurantOrderItem::query()->create([
                'restaurant_order_id' => $order->id,
                'menu_item_id' => $menuItem->id,
                'item_name' => $menuItem->name,
                'quantity' => $quantity,
                'unit_price' => 40,
                'total_price' => 40 * $quantity,
            ]);
        }

        $reportPage = new RestaurantOrderReport;
        $reportPage->period = 'custom';
        $reportPage->startDate = '2026-09-01';
        $reportPage->endDate = '2026-09-30';
        $reportedOrder = $reportPage->paginatedOrders()->first();

        self::assertFalse($reportedOrder->relationLoaded('items'));
        self::assertSame(5, (int) $reportedOrder->items_sum_quantity);
        self::assertTrue($reportedOrder->relationLoaded('guest'));
        self::assertEqualsCanonicalizing(
            ['id', 'first_name', 'last_name'],
            array_keys($reportedOrder->guest->getAttributes()),
        );
        self::assertEqualsCanonicalizing([
            'id',
            'guest_id',
            'order_number',
            'customer_email',
            'ordering_channel',
            'status',
            'payment_status',
            'total',
            'created_at',
            'items_sum_quantity',
        ], array_keys($reportedOrder->getAttributes()));
    }

    public function test_restaurant_report_page_calculates_its_metrics_only_once(): void
    {
        Role::findOrCreate('accountant', 'web');
        $accountant = User::factory()->create(['department' => 'accountant']);
        $accountant->assignRole('accountant');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $order = RestaurantOrder::query()->create([
            'order_number' => 'FOOD-REPORT-ONCE',
            'subtotal' => 120,
            'total' => 120,
            'status' => 'served',
            'payment_status' => 'completed',
        ]);
        $menuItem = $this->menuItem('rendered-total');
        RestaurantOrderItem::query()->create([
            'restaurant_order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'item_name' => $menuItem->name,
            'quantity' => 4,
            'unit_price' => 30,
            'total_price' => 120,
        ]);
        Payment::query()->create([
            'restaurant_order_id' => $order->id,
            'amount' => 120,
            'method' => 'card',
            'payment_status' => 'completed',
            'transaction_reference' => 'FOOD-REPORT-ONCE-PAYMENT',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $component = Livewire::actingAs($accountant)
                ->test(RestaurantOrderReport::class)
                ->assertSuccessful();
            $reportQueries = collect(DB::getQueryLog())
                ->pluck('query')
                ->filter(fn (string $query): bool => (bool) preg_match(
                    '/\b(?:from|join)\s+["`]?(?:restaurant_orders|restaurant_order_items|payments)["`]?\b/i',
                    $query,
                ));
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }

        $component->assertSee('GHS 120.00 collected · GHS 0.00 refunded');
        $component->assertSee('4 items');
        $component->assertSeeInOrder([
            'Live kitchen queue',
            'Current active orders across all order dates',
            'Not affected by the selected report period',
        ]);
        self::assertCount(11, $reportQueries, $reportQueries->implode(PHP_EOL));
    }

    public function test_restaurant_report_exposes_an_accessible_loading_state_for_result_refreshes(): void
    {
        $this->travelTo('2026-09-05 09:00:00');
        RestaurantOrder::query()->create([
            'order_number' => 'FOOD-LOADING-STATE',
            'subtotal' => 75,
            'total' => 75,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);

        Role::findOrCreate('accountant', 'web');
        $accountant = User::factory()->create(['department' => 'accountant']);
        $accountant->assignRole('accountant');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $response = $this->actingAs($accountant)->get(RestaurantOrderReport::getUrl());

        $response->assertOk();

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $loadingTargets = 'applyReportPeriod,resetReportPeriod,perPage,registerSearch,paymentStatus,fulfillmentStatus,orderingChannel,resetRegisterFilters,gotoPage,previousPage,nextPage';
        $reportRegion = $xpath->query('//section[@aria-label="Restaurant report results"]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $reportRegion);
        self::assertSame('aria-busy', $reportRegion->getAttribute('wire:loading.attr'));
        self::assertSame($loadingTargets, $reportRegion->getAttribute('wire:target'));

        $results = $xpath->query('./div[@data-restaurant-report-results]', $reportRegion)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $results);
        self::assertSame($loadingTargets, $results->getAttribute('wire:target'));
        self::assertStringContainsString('pointer-events-none', $results->getAttribute('wire:loading.class'));
        self::assertStringContainsString('opacity-60', $results->getAttribute('wire:loading.class'));
        self::assertStringContainsString('Live kitchen queue', $results->textContent);
        self::assertStringContainsString('Order register', $results->textContent);

        $status = $xpath->query('./div[@data-restaurant-report-loading-overlay and @role="status"]', $reportRegion)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $status);
        self::assertSame('polite', $status->getAttribute('aria-live'));
        self::assertSame('true', $status->getAttribute('aria-atomic'));
        self::assertSame($loadingTargets, $status->getAttribute('wire:target'));
        self::assertTrue($status->hasAttribute('wire:loading.flex'));
        self::assertStringContainsString('Updating restaurant report', $status->textContent);

        $pageSize = $xpath->query('//select[@id="restaurant-report-per-page"]')?->item(0);
        self::assertInstanceOf(\DOMElement::class, $pageSize);
        self::assertSame('disabled', $pageSize->getAttribute('wire:loading.attr'));
        self::assertSame('perPage', $pageSize->getAttribute('wire:target'));
        self::assertSame(0, $xpath->query('.//form[@*[name()="wire:submit"]="applyReportPeriod"]', $reportRegion)?->count());
    }

    public function test_period_activity_excludes_the_live_queue_but_includes_payment_events(): void
    {
        $this->travelTo('2026-07-10 09:00:00');
        $olderActiveOrder = RestaurantOrder::query()->create([
            'order_number' => 'FOOD-OLDER-LIVE-QUEUE',
            'subtotal' => 120,
            'total' => 120,
            'status' => 'confirmed',
            'payment_status' => 'completed',
        ]);

        $reportPage = new RestaurantOrderReport;
        $reportPage->period = 'custom';
        $reportPage->startDate = '2026-08-01';
        $reportPage->endDate = '2026-08-31';
        $emptyMetrics = $reportPage->getReportMetrics();

        self::assertSame(0, $emptyMetrics['totalOrders']);
        self::assertSame(1, $emptyMetrics['liveKitchenOrders']);
        self::assertArrayHasKey('hasPeriodActivity', $emptyMetrics);
        self::assertFalse($emptyMetrics['hasPeriodActivity']);

        $this->travelTo('2026-08-05 09:00:00');
        Payment::query()->create([
            'restaurant_order_id' => $olderActiveOrder->id,
            'amount' => 120,
            'method' => 'card',
            'payment_status' => 'completed',
            'transaction_reference' => 'FOOD-OLDER-LIVE-QUEUE-PAYMENT',
        ]);

        $paymentMetrics = $reportPage->getReportMetrics();

        self::assertSame(0, $paymentMetrics['totalOrders']);
        self::assertSame(120.0, $paymentMetrics['revenue']);
        self::assertTrue($paymentMetrics['hasPeriodActivity']);
    }

    public function test_refund_events_are_treated_as_period_activity_without_new_orders(): void
    {
        $this->travelTo('2026-07-10 09:00:00');
        $order = RestaurantOrder::query()->create([
            'order_number' => 'FOOD-REFUND-ONLY-PERIOD',
            'subtotal' => 80,
            'total' => 80,
            'status' => 'served',
            'payment_status' => 'refunded',
        ]);
        $payment = Payment::query()->create([
            'restaurant_order_id' => $order->id,
            'amount' => 80,
            'method' => 'card',
            'payment_status' => 'completed',
            'transaction_reference' => 'FOOD-REFUND-ONLY-PERIOD-PAYMENT',
        ]);

        $this->travelTo('2026-08-12 09:00:00');
        $payment->update(['payment_status' => 'refunded']);

        $reportPage = new RestaurantOrderReport;
        $reportPage->period = 'custom';
        $reportPage->startDate = '2026-08-01';
        $reportPage->endDate = '2026-08-31';
        $metrics = $reportPage->getReportMetrics();

        self::assertSame(0, $metrics['totalOrders']);
        self::assertSame(0.0, $metrics['revenue']);
        self::assertSame(80.0, $metrics['refunds']);
        self::assertTrue($metrics['hasPeriodActivity']);
    }

    public function test_empty_period_renders_one_actionable_state_and_preserves_the_live_queue_count(): void
    {
        $this->travelTo('2026-07-10 09:00:00');
        RestaurantOrder::query()->create([
            'order_number' => 'FOOD-OUTSIDE-EMPTY-PERIOD',
            'subtotal' => 60,
            'total' => 60,
            'status' => 'preparing',
            'payment_status' => 'completed',
        ]);

        Role::findOrCreate('accountant', 'web');
        $accountant = User::factory()->create(['department' => 'accountant']);
        $accountant->assignRole('accountant');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $response = $this->actingAs($accountant)->get(RestaurantOrderReport::getUrl([
            'period' => 'custom',
            'startDate' => '2026-08-01',
            'endDate' => '2026-08-31',
        ]));

        $response->assertOk();
        $response->assertSee('No restaurant activity in this period');
        $response->assertSee('1 active order is currently in the live kitchen queue');
        $response->assertSee('Change reporting period');
        $response->assertDontSee('Orders received');
        $response->assertDontSee('Restaurant performance comparison');
        $response->assertDontSee('Order volume trend');
        $response->assertDontSee('Restaurant revenue trend');
        $response->assertDontSee('Payment status');
        $response->assertDontSee('Order register');

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $emptyState = $xpath->query('//*[@data-restaurant-report-empty-state and @role="status"]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $emptyState);
        self::assertSame(
            '#restaurant-report-period-controls',
            $xpath->query('.//a[contains(normalize-space(.), "Change reporting period")]', $emptyState)?->item(0)?->getAttribute('href'),
        );
        self::assertSame(1, $xpath->query('//form[@id="restaurant-report-period-controls"]')?->count());
    }

    public function test_order_register_search_matches_references_and_guest_email_fields(): void
    {
        $this->travelTo('2026-09-05 09:00:00');
        $guest = Guest::query()->create([
            'first_name' => 'Ama',
            'last_name' => 'Owusu',
            'email' => 'ama.owusu@example.test',
        ]);
        $guestOrder = RestaurantOrder::query()->create([
            'guest_id' => $guest->id,
            'order_number' => 'FOOD-GUEST-REFERENCE',
            'ordering_channel' => 'web',
            'subtotal' => 90,
            'total' => 90,
            'status' => 'served',
            'payment_status' => 'completed',
        ]);
        $walkInOrder = RestaurantOrder::query()->create([
            'order_number' => 'FOOD-WALK-IN',
            'customer_email' => 'walkin@example.test',
            'ordering_channel' => 'staff',
            'subtotal' => 40,
            'total' => 40,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $reportPage = new RestaurantOrderReport;
        $reportPage->period = 'custom';
        $reportPage->startDate = '2026-09-01';
        $reportPage->endDate = '2026-09-30';

        $reportPage->registerSearch = 'guest-reference';
        self::assertSame([$guestOrder->id], $reportPage->paginatedOrders()->pluck('id')->all());

        $reportPage->registerSearch = 'ama.owusu@example.test';
        self::assertSame([$guestOrder->id], $reportPage->paginatedOrders()->pluck('id')->all());

        $reportPage->registerSearch = 'walkin@example.test';
        self::assertSame([$walkInOrder->id], $reportPage->paginatedOrders()->pluck('id')->all());
    }

    public function test_order_register_combines_valid_filters_without_narrowing_period_metrics(): void
    {
        $this->travelTo('2026-09-05 09:00:00');
        $matchingOrder = RestaurantOrder::query()->create([
            'order_number' => 'FOOD-FILTER-MATCH',
            'ordering_channel' => 'qr',
            'subtotal' => 100,
            'total' => 100,
            'status' => 'ready',
            'payment_status' => 'completed',
        ]);
        RestaurantOrder::query()->create([
            'order_number' => 'FOOD-WRONG-PAYMENT',
            'ordering_channel' => 'qr',
            'subtotal' => 75,
            'total' => 75,
            'status' => 'ready',
            'payment_status' => 'pending',
        ]);
        RestaurantOrder::query()->create([
            'order_number' => 'FOOD-WRONG-CHANNEL',
            'ordering_channel' => 'web',
            'subtotal' => 50,
            'total' => 50,
            'status' => 'ready',
            'payment_status' => 'completed',
        ]);

        $reportPage = new RestaurantOrderReport;
        $reportPage->period = 'custom';
        $reportPage->startDate = '2026-09-01';
        $reportPage->endDate = '2026-09-30';
        $reportPage->paymentStatus = 'completed';
        $reportPage->fulfillmentStatus = 'ready';
        $reportPage->orderingChannel = 'qr';
        $report = $reportPage->getReportData();

        self::assertSame(3, $report['totalOrders']);
        self::assertSame(1, $report['orders']->total());
        self::assertSame($matchingOrder->id, $report['orders']->first()->id);

        $reportPage->paymentStatus = 'not-a-payment-status';
        $reportPage->fulfillmentStatus = 'not-an-order-status';
        $reportPage->orderingChannel = 'not-a-channel';

        self::assertSame(3, $reportPage->paginatedOrders()->total());
    }

    public function test_order_register_filter_changes_and_clear_action_reset_pagination(): void
    {
        Role::findOrCreate('accountant', 'web');
        $accountant = User::factory()->create(['department' => 'accountant']);
        $accountant->assignRole('accountant');

        $component = Livewire::actingAs($accountant)->test(RestaurantOrderReport::class);

        foreach ([
            'registerSearch' => 'FOOD',
            'paymentStatus' => 'completed',
            'fulfillmentStatus' => 'ready',
            'orderingChannel' => 'qr',
        ] as $property => $value) {
            $component
                ->set('paginators.orders_page', 3)
                ->set($property, $value)
                ->assertSet('paginators.orders_page', 1);
        }

        $component
            ->set('paginators.orders_page', 3)
            ->call('resetRegisterFilters')
            ->assertSet('registerSearch', '')
            ->assertSet('paymentStatus', '')
            ->assertSet('fulfillmentStatus', '')
            ->assertSet('orderingChannel', '')
            ->assertSet('paginators.orders_page', 1);
    }

    public function test_order_register_renders_responsive_controls_and_only_authorized_detail_links(): void
    {
        $this->travelTo('2026-09-05 09:00:00');
        $order = RestaurantOrder::query()->create([
            'order_number' => 'FOOD-PERMISSION-LINK',
            'ordering_channel' => 'web',
            'subtotal' => 110,
            'total' => 110,
            'status' => 'confirmed',
            'payment_status' => 'completed',
        ]);
        Role::findOrCreate('manager', 'web');
        Permission::findOrCreate('manage kitchen orders', 'web');
        $manager = User::factory()->create(['department' => 'management']);
        $manager->assignRole('manager');
        $manager->givePermissionTo('manage kitchen orders');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $managerResponse = $this->actingAs($manager)->get(RestaurantOrderReport::getUrl());
        $managerResponse->assertOk();
        $managerDocument = new \DOMDocument;
        @$managerDocument->loadHTML($managerResponse->getContent());
        $managerXPath = new \DOMXPath($managerDocument);
        $editUrl = RestaurantOrderResource::getUrl('edit', ['record' => $order]);

        self::assertSame(1, $managerXPath->query('//*[@data-restaurant-register-filters]')?->count());
        self::assertSame(1, $managerXPath->query('//input[@id="restaurant-register-search"]')?->count());
        self::assertSame(1, $managerXPath->query('//select[@id="restaurant-payment-status"]')?->count());
        self::assertSame(1, $managerXPath->query('//select[@id="restaurant-fulfillment-status"]')?->count());
        self::assertSame(1, $managerXPath->query('//select[@id="restaurant-ordering-channel"]')?->count());
        self::assertSame(2, $managerXPath->query('//a[@data-restaurant-order-link and @href="'.$editUrl.'"]')?->count());
        self::assertStringContainsString('Showing 1 of 1 order', $managerResponse->getContent());

        Role::findOrCreate('accountant', 'web');
        $accountant = User::factory()->create(['department' => 'accountant']);
        $accountant->assignRole('accountant');
        $accountantResponse = $this->actingAs($accountant)->get(RestaurantOrderReport::getUrl());
        $accountantResponse->assertOk();
        $accountantDocument = new \DOMDocument;
        @$accountantDocument->loadHTML($accountantResponse->getContent());
        $accountantXPath = new \DOMXPath($accountantDocument);

        self::assertSame(0, $accountantXPath->query('//a[@data-restaurant-order-link]')?->count());
        self::assertStringContainsString('FOOD-PERMISSION-LINK', $accountantResponse->getContent());
    }

    public function test_cancelled_orders_do_not_inflate_order_payment_outcome_metrics(): void
    {
        $this->travelTo('2026-09-18 14:30:00');

        foreach ([
            ['order_number' => 'FOOD-PAID', 'status' => 'served', 'payment_status' => 'completed', 'total' => 100],
            ['order_number' => 'FOOD-PENDING', 'status' => 'pending', 'payment_status' => 'pending', 'total' => 80],
            ['order_number' => 'FOOD-CANCELLED-PAID', 'status' => 'cancelled', 'payment_status' => 'completed', 'total' => 200],
            ['order_number' => 'FOOD-CANCELLED-PENDING', 'status' => 'cancelled', 'payment_status' => 'pending', 'total' => 90],
            ['order_number' => 'FOOD-FAILED', 'status' => 'pending', 'payment_status' => 'failed', 'total' => 70],
            ['order_number' => 'FOOD-REFUNDED', 'status' => 'served', 'payment_status' => 'refunded', 'total' => 50],
        ] as $order) {
            RestaurantOrder::query()->create([
                ...$order,
                'subtotal' => $order['total'],
            ]);
        }

        $metrics = (new RestaurantOrderReport)->getReportMetrics();

        self::assertSame(6, $metrics['totalOrders']);
        self::assertSame(1, $metrics['paidOrders']);
        self::assertSame(1, $metrics['pendingOrders']);
        self::assertSame(2, $metrics['cancelledOrders']);
        self::assertSame(25.0, $metrics['paymentRate']);
    }

    public function test_financial_metrics_follow_payment_and_refund_event_dates(): void
    {
        $this->travelTo('2026-07-10 09:00:00');
        $paidOrder = RestaurantOrder::query()->create([
            'order_number' => 'FOOD-LATER-PAYMENT',
            'subtotal' => 300,
            'total' => 300,
            'status' => 'served',
            'payment_status' => 'completed',
        ]);
        $refundedOrder = RestaurantOrder::query()->create([
            'order_number' => 'FOOD-LATER-REFUND',
            'subtotal' => 50,
            'total' => 50,
            'status' => 'served',
            'payment_status' => 'refunded',
        ]);

        $this->travelTo('2026-07-15 09:00:00');
        $refundedPayment = Payment::query()->create([
            'restaurant_order_id' => $refundedOrder->id,
            'amount' => 50,
            'method' => 'card',
            'payment_status' => 'completed',
            'transaction_reference' => 'FOOD-REFUND-EVENT',
        ]);

        $this->travelTo('2026-08-05 09:00:00');
        Payment::query()->create([
            'restaurant_order_id' => $paidOrder->id,
            'amount' => 120,
            'method' => 'card',
            'payment_status' => 'completed',
            'transaction_reference' => 'FOOD-LATER-PAYMENT-1',
        ]);

        $this->travelTo('2026-08-07 09:00:00');
        Payment::query()->create([
            'restaurant_order_id' => $paidOrder->id,
            'amount' => 80,
            'method' => 'card',
            'payment_status' => 'completed',
            'transaction_reference' => 'FOOD-LATER-PAYMENT-2',
        ]);

        $this->travelTo('2026-08-20 12:00:00');
        $refundedPayment->update(['payment_status' => 'refunded']);

        $reportPage = new RestaurantOrderReport;
        $reportPage->period = 'custom';
        $reportPage->startDate = '2026-08-01';
        $reportPage->endDate = '2026-08-31';
        $metrics = $reportPage->getReportMetrics();

        self::assertSame(200.0, $metrics['revenue']);
        self::assertSame(50.0, $metrics['refunds']);
        self::assertSame(150.0, $metrics['netRevenue']);
        self::assertSame(1, $metrics['collectedOrderCount']);
        self::assertSame(200.0, $metrics['averageOrderValue']);
    }

    public function test_outstanding_uses_remaining_balances_after_collected_payments(): void
    {
        $this->travelTo('2026-08-10 09:00:00');
        $partiallyPaid = RestaurantOrder::query()->create([
            'order_number' => 'FOOD-PARTIAL',
            'subtotal' => 400,
            'total' => 400,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);
        $overpaid = RestaurantOrder::query()->create([
            'order_number' => 'FOOD-OVERPAID',
            'subtotal' => 100,
            'total' => 100,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);
        RestaurantOrder::query()->create([
            'order_number' => 'FOOD-CANCELLED-UNPAID',
            'subtotal' => 200,
            'total' => 200,
            'status' => 'cancelled',
            'payment_status' => 'pending',
        ]);
        RestaurantOrder::query()->create([
            'order_number' => 'FOOD-SETTLED-NO-BALANCE',
            'subtotal' => 300,
            'total' => 300,
            'status' => 'served',
            'payment_status' => 'completed',
        ]);

        Payment::query()->create([
            'restaurant_order_id' => $partiallyPaid->id,
            'amount' => 150,
            'method' => 'card',
            'payment_status' => 'completed',
            'transaction_reference' => 'FOOD-PARTIAL-PAYMENT',
        ]);
        Payment::query()->create([
            'restaurant_order_id' => $overpaid->id,
            'amount' => 150,
            'method' => 'card',
            'payment_status' => 'completed',
            'transaction_reference' => 'FOOD-OVERPAYMENT',
        ]);

        $reportPage = new RestaurantOrderReport;
        $reportPage->period = 'custom';
        $reportPage->startDate = '2026-08-01';
        $reportPage->endDate = '2026-08-31';

        self::assertSame(250.0, $reportPage->getReportMetrics()['outstanding']);
    }

    public function test_restaurant_order_report_has_a_mobile_card_register_and_pagination_controls(): void
    {
        $view = file_get_contents(resource_path('views/filament/admin/pages/restaurant-order-report.blade.php'));

        self::assertStringContainsString('md:hidden', $view);
        self::assertStringContainsString('Order cards', $view);
        self::assertStringContainsString('hasPages()', $view);
        self::assertStringContainsString('$report[\'orders\']->links()', $view);
        self::assertStringContainsString('Rows per page', $view);
    }

    public function test_mobile_order_cards_preserve_long_references_for_editable_and_view_only_orders(): void
    {
        $this->travelTo('2026-09-05 09:00:00');
        $reference = 'FOOD-20260905-CORPORATE-CONFERENCE-GUEST-ORDER-REFERENCE-000001';
        $order = RestaurantOrder::query()->create([
            'order_number' => $reference,
            'ordering_channel' => 'web',
            'subtotal' => 175,
            'total' => 175,
            'status' => 'confirmed',
            'payment_status' => 'completed',
        ]);
        Role::findOrCreate('manager', 'web');
        Permission::findOrCreate('manage kitchen orders', 'web');
        $manager = User::factory()->create(['department' => 'management']);
        $manager->assignRole('manager');
        $manager->givePermissionTo('manage kitchen orders');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $managerResponse = $this->actingAs($manager)->get(RestaurantOrderReport::getUrl());
        $managerResponse->assertOk();
        $managerDocument = new \DOMDocument;
        @$managerDocument->loadHTML($managerResponse->getContent());
        $managerXPath = new \DOMXPath($managerDocument);
        $mobileCard = $managerXPath->query('//div[@aria-label="Order cards"]//article')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $mobileCard);
        $referenceBlock = $managerXPath->query('.//*[@data-mobile-order-reference]', $mobileCard)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $referenceBlock);
        self::assertStringContainsString($reference, $referenceBlock->textContent);
        self::assertStringContainsString('min-w-0', $referenceBlock->getAttribute('class'));

        $referenceLink = $managerXPath->query('.//a[@data-restaurant-order-link]', $referenceBlock)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $referenceLink);
        self::assertSame('Open order '.$reference, $referenceLink->getAttribute('aria-label'));
        self::assertStringContainsString('max-w-full', $referenceLink->getAttribute('class'));
        self::assertStringContainsString('min-w-0', $referenceLink->getAttribute('class'));
        self::assertStringContainsString('items-start', $referenceLink->getAttribute('class'));

        $referenceText = $managerXPath->query('.//*[@data-mobile-order-reference-text]', $referenceLink)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $referenceText);
        self::assertSame($reference, trim($referenceText->textContent));
        self::assertStringContainsString('min-w-0', $referenceText->getAttribute('class'));
        self::assertStringContainsString('break-all', $referenceText->getAttribute('class'));
        self::assertStringNotContainsString('truncate', $referenceText->getAttribute('class'));

        $total = $managerXPath->query('./*[@data-mobile-order-total]', $mobileCard)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $total);
        self::assertStringContainsString('GHS 175.00', $total->textContent);

        Role::findOrCreate('accountant', 'web');
        $accountant = User::factory()->create(['department' => 'accountant']);
        $accountant->assignRole('accountant');
        $accountantResponse = $this->actingAs($accountant)->get(RestaurantOrderReport::getUrl());
        $accountantResponse->assertOk();
        $accountantDocument = new \DOMDocument;
        @$accountantDocument->loadHTML($accountantResponse->getContent());
        $accountantXPath = new \DOMXPath($accountantDocument);
        $viewOnlyReference = $accountantXPath->query('//div[@aria-label="Order cards"]//*[@data-mobile-order-reference-text]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $viewOnlyReference);
        self::assertSame($reference, trim($viewOnlyReference->textContent));
        self::assertStringContainsString('break-all', $viewOnlyReference->getAttribute('class'));
        self::assertSame(0, $accountantXPath->query('//div[@aria-label="Order cards"]//a[@data-restaurant-order-link]')?->count());
    }

    public function test_order_register_uses_consistent_status_channel_and_count_presentation(): void
    {
        $this->travelTo('2026-09-05 09:00:00');
        $menuItem = $this->menuItem('presentation');
        $failedQrOrder = RestaurantOrder::query()->create([
            'order_number' => 'FOOD-PRESENTATION-FAILED-QR',
            'ordering_channel' => 'qr',
            'subtotal' => 40,
            'total' => 40,
            'status' => 'confirmed',
            'payment_status' => 'failed',
        ]);
        RestaurantOrderItem::query()->create([
            'restaurant_order_id' => $failedQrOrder->id,
            'menu_item_id' => $menuItem->id,
            'item_name' => $menuItem->name,
            'quantity' => 1,
            'unit_price' => 40,
            'total_price' => 40,
        ]);
        Role::findOrCreate('accountant', 'web');
        $accountant = User::factory()->create(['department' => 'accountant']);
        $accountant->assignRole('accountant');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $reportPage = new RestaurantOrderReport;
        self::assertSame('Website', $reportPage->orderingChannelLabel('web'));
        self::assertSame('Table QR', $reportPage->orderingChannelLabel('qr'));
        self::assertSame('Staff entry', $reportPage->orderingChannelLabel('staff'));
        self::assertSame('danger', $reportPage->paymentStatusColor('failed'));
        self::assertSame('info', $reportPage->paymentStatusColor('refunded'));

        $response = $this->actingAs($accountant)->get(RestaurantOrderReport::getUrl());
        $response->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $mobileCard = $xpath->query('//div[@aria-label="Order cards"]/article[.//*[@data-mobile-order-reference-text and normalize-space(.)="FOOD-PRESENTATION-FAILED-QR"]]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $mobileCard);
        $mobileChannel = $xpath->query('.//dt[normalize-space(.)="Channel"]/following-sibling::dd[1]', $mobileCard)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $mobileChannel);
        self::assertSame('Table QR', trim($mobileChannel->textContent));
        $mobilePayment = $xpath->query('.//dt[normalize-space(.)="Payment"]/following-sibling::dd[1]//*[contains(concat(" ", normalize-space(@class), " "), " fi-badge ")]', $mobileCard)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $mobilePayment);
        self::assertSame('Failed', trim($mobilePayment->textContent));
        self::assertStringContainsString('fi-color-danger', $mobilePayment->getAttribute('class'));

        $desktopRow = $xpath->query('//table//tbody/tr[.//th[contains(normalize-space(.), "FOOD-PRESENTATION-FAILED-QR")]]')?->item(0);
        self::assertInstanceOf(\DOMElement::class, $desktopRow);
        self::assertStringContainsString('1 item', $desktopRow->textContent);
        self::assertStringContainsString('Table QR', $desktopRow->textContent);
        $desktopPayment = $xpath->query('./td[3]/*[contains(concat(" ", normalize-space(@class), " "), " fi-badge ")]', $desktopRow)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $desktopPayment);
        self::assertSame('Failed', trim($desktopPayment->textContent));
        self::assertStringContainsString('fi-color-danger', $desktopPayment->getAttribute('class'));
    }

    private function menuItem(string $suffix): MenuItem
    {
        $category = MenuCategory::query()->create([
            'name' => 'Main meals',
            'slug' => 'main-meals-'.$suffix,
        ]);

        return MenuItem::query()->create([
            'menu_category_id' => $category->id,
            'name' => 'Jollof rice',
            'slug' => 'jollof-rice-'.$suffix,
            'price' => 40,
        ]);
    }
}
