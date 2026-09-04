<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboards\TransactionDashboard;
use App\Filament\Admin\Pages\RevenueReport;
use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Filament\Admin\Widgets\RevenueComparisonStats;
use App\Filament\Admin\Widgets\RevenueReportStats;
use App\Filament\Admin\Widgets\RevenueTrendChart;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\ConferenceRoom;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RevenueReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenue_report_uses_one_selected_period_for_structured_financial_data(): void
    {
        $reportPage = new RevenueReport;
        $reportPage->period = 'quarterly';

        self::assertSame('Quarterly', $reportPage->periodLabel());

        $report = $reportPage->report();

        self::assertArrayHasKey('netRevenue', $report);
        self::assertArrayHasKey('outstandingBreakdown', $report);
        self::assertArrayHasKey('paymentsReceived', $report);
        self::assertArrayHasKey('food', $report['outstandingBreakdown']);
    }

    public function test_revenue_report_groups_collected_payments_by_business_channel(): void
    {
        [$guest, $room, $conferenceRoom, $restaurant, $table] = $this->serviceFixture();
        $hotel = Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-11',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $conference = ConferenceBooking::query()->create([
            'guest_id' => $guest->id,
            'conference_room_id' => $conferenceRoom->id,
            'booking_date' => '2026-10-10',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => 200,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $reservation = RestaurantReservation::query()->create([
            'restaurant_id' => $restaurant->id,
            'restaurant_table_id' => $table->id,
            'guest_id' => $guest->id,
            'guest_name' => $guest->full_name,
            'guest_email' => $guest->email,
            'guest_phone' => $guest->phone_number,
            'reservation_date' => '2026-10-10',
            'reservation_time' => '18:00',
            'number_of_guests' => 2,
            'reservation_fee' => 300,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $food = RestaurantOrder::query()->create([
            'guest_id' => $guest->id,
            'order_number' => 'REVENUE-CHANNEL-ORDER',
            'total' => 400,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $this->createdAt($this->payment($guest, 'booking_id', $hotel->id, 100, 'REVENUE-CHANNEL-HOTEL'), '2026-08-05 09:00:00');
        $this->createdAt($this->payment($guest, 'conference_booking_id', $conference->id, 200, 'REVENUE-CHANNEL-CONFERENCE'), '2026-08-06 09:00:00');
        $this->createdAt($this->payment($guest, 'restaurant_reservation_id', $reservation->id, 300, 'REVENUE-CHANNEL-TABLE'), '2026-08-07 09:00:00');
        $this->createdAt($this->payment($guest, 'restaurant_order_id', $food->id, 400, 'REVENUE-CHANNEL-FOOD'), '2026-08-08 09:00:00');
        $this->createdAt($this->payment($guest, 'booking_id', null, 50, 'REVENUE-CHANNEL-DIRECT'), '2026-08-09 09:00:00');

        $report = $this->reportForPeriod('2026-08-01', '2026-08-31');

        self::assertArrayHasKey('revenueByChannel', $report);
        self::assertSame([
            'hotel' => ['total' => 100.0, 'payment_count' => 1],
            'conference' => ['total' => 200.0, 'payment_count' => 1],
            'table' => ['total' => 300.0, 'payment_count' => 1],
            'food' => ['total' => 400.0, 'payment_count' => 1],
            'other' => ['total' => 50.0, 'payment_count' => 1],
        ], $report['revenueByChannel']);
        self::assertSame(
            $report['revenue'],
            array_sum(array_column($report['revenueByChannel'], 'total')),
        );
    }

    public function test_revenue_report_compares_the_immediately_preceding_equal_length_period_and_fills_trend_gaps(): void
    {
        [$guest] = $this->serviceFixture();

        $this->createdAt(
            $this->payment($guest, 'booking_id', null, 100, 'REVENUE-COMPARISON-PREVIOUS'),
            '2026-08-08 09:00:00',
        );
        $this->createdAt(
            $this->payment($guest, 'booking_id', null, 200, 'REVENUE-COMPARISON-CURRENT-ONE'),
            '2026-08-10 09:00:00',
        );
        $this->createdAt(
            $this->payment($guest, 'booking_id', null, 100, 'REVENUE-COMPARISON-CURRENT-TWO'),
            '2026-08-12 15:00:00',
        );

        $previousRefund = $this->createdAt(
            $this->payment($guest, 'booking_id', null, 20, 'REVENUE-COMPARISON-PREVIOUS-REFUND'),
            '2026-07-01 09:00:00',
        );
        $previousRefund->forceFill([
            'payment_status' => 'refunded',
            'refunded_at' => Carbon::parse('2026-08-08 12:00:00'),
        ])->saveQuietly();

        $currentRefund = $this->createdAt(
            $this->payment($guest, 'booking_id', null, 30, 'REVENUE-COMPARISON-CURRENT-REFUND'),
            '2026-07-02 09:00:00',
        );
        $currentRefund->forceFill([
            'payment_status' => 'refunded',
            'refunded_at' => Carbon::parse('2026-08-11 12:00:00'),
        ])->saveQuietly();

        $report = $this->reportForPeriod('2026-08-10', '2026-08-12');

        self::assertSame('Aug 7, 2026 to Aug 9, 2026', $report['comparison']['previousPeriodLabel']);
        self::assertSame([
            'current' => 300.0,
            'previous' => 100.0,
            'difference' => 200.0,
            'percentageChange' => 200.0,
        ], $report['comparison']['revenue']);
        self::assertSame([
            'current' => 30.0,
            'previous' => 20.0,
            'difference' => 10.0,
            'percentageChange' => 50.0,
        ], $report['comparison']['refunds']);
        self::assertSame([
            'current' => 270.0,
            'previous' => 80.0,
            'difference' => 190.0,
            'percentageChange' => 237.5,
        ], $report['comparison']['netRevenue']);
        self::assertSame([
            'granularity' => 'day',
            'labels' => ['Aug 10', 'Aug 11', 'Aug 12'],
            'collected' => [200.0, 0.0, 100.0],
            'refunds' => [0.0, 30.0, 0.0],
            'net' => [200.0, -30.0, 100.0],
        ], $report['trend']);
    }

    public function test_revenue_comparison_uses_null_percentage_when_the_previous_value_is_zero(): void
    {
        [$guest] = $this->serviceFixture();
        $this->createdAt(
            $this->payment($guest, 'booking_id', null, 75, 'REVENUE-COMPARISON-NO-BASELINE'),
            '2026-08-10 09:00:00',
        );

        $comparison = $this->reportForPeriod('2026-08-10', '2026-08-10')['comparison'];

        self::assertSame('Aug 9, 2026', $comparison['previousPeriodLabel']);
        self::assertSame(75.0, $comparison['revenue']['difference']);
        self::assertNull($comparison['revenue']['percentageChange']);
    }

    public function test_revenue_comparison_stats_widget_uses_precomputed_values_without_queries(): void
    {
        self::assertTrue(is_subclass_of(RevenueComparisonStats::class, StatsOverviewWidget::class));

        $widget = new RevenueComparisonStats;
        $widget->comparison = [
            'previousPeriodLabel' => 'Aug 7, 2026 to Aug 9, 2026',
            'revenue' => ['current' => 300.0, 'previous' => 100.0, 'difference' => 200.0, 'percentageChange' => 200.0],
            'refunds' => ['current' => 30.0, 'previous' => 20.0, 'difference' => 10.0, 'percentageChange' => 50.0],
            'netRevenue' => ['current' => 270.0, 'previous' => 80.0, 'difference' => 190.0, 'percentageChange' => 237.5],
        ];
        $widget->drillDownUrls = [
            'revenue' => '/admin/payments?scope=revenue',
            'refunds' => '/admin/payments?scope=refunds',
            'netRevenue' => '/admin/transaction-dashboard#collection-performance',
        ];
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $stats = $method->invoke($widget);
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
        }

        self::assertCount(0, $queries);
        self::assertSame(
            ['Collected revenue change', 'Refund change', 'Net revenue change'],
            array_map(fn ($stat): string => $stat->getLabel(), $stats),
        );
        self::assertSame(
            ['+GHS 200.00', '+GHS 10.00', '+GHS 190.00'],
            array_map(fn ($stat): string => $stat->getValue(), $stats),
        );
        self::assertSame(['success', 'danger', 'success'], array_map(
            fn ($stat): string|array|null => $stat->getColor(),
            $stats,
        ));
        self::assertStringContainsString('Current GHS 300.00', $stats[0]->getDescription());
        self::assertStringContainsString('Previous GHS 100.00', $stats[0]->getDescription());
        self::assertStringContainsString('Up 200.0%', $stats[0]->getDescription());
        self::assertSame('/admin/payments?scope=revenue', $stats[0]->getUrl());
        self::assertSame('/admin/payments?scope=refunds', $stats[1]->getUrl());
        self::assertSame('/admin/transaction-dashboard#collection-performance', $stats[2]->getUrl());
    }

    public function test_revenue_trend_chart_uses_precomputed_values_without_queries(): void
    {
        self::assertTrue(is_subclass_of(RevenueTrendChart::class, ChartWidget::class));

        $widget = new RevenueTrendChart;
        $widget->trend = [
            'granularity' => 'day',
            'labels' => ['Aug 10', 'Aug 11', 'Aug 12'],
            'collected' => [200.0, 0.0, 100.0],
            'refunds' => [0.0, 30.0, 0.0],
            'net' => [200.0, -30.0, 100.0],
        ];
        $method = new \ReflectionMethod($widget, 'getData');
        $method->setAccessible(true);

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $data = $method->invoke($widget);
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
        }

        self::assertCount(0, $queries);
        self::assertSame(['Aug 10', 'Aug 11', 'Aug 12'], $data['labels']);
        self::assertSame(
            ['Collected revenue', 'Refunds', 'Net revenue'],
            array_column($data['datasets'], 'label'),
        );
        self::assertSame('line', $data['datasets'][2]['type']);
        self::assertSame('#059669', $data['datasets'][0]['borderColor']);
        self::assertSame('#E11D48', $data['datasets'][1]['borderColor']);
        self::assertSame('#4F46E5', $data['datasets'][2]['borderColor']);
    }

    public function test_revenue_trend_uses_readable_automatic_granularity_for_short_and_long_ranges(): void
    {
        $daily = $this->reportForPeriod('2026-08-10', '2026-08-10')['trend'];
        $medium = $this->reportForPeriod('2026-08-01', '2026-09-15')['trend'];
        $long = $this->reportForPeriod('2024-01-01', '2026-01-01')['trend'];

        self::assertSame('hour', $daily['granularity']);
        self::assertCount(24, $daily['labels']);
        self::assertSame(['12 AM', '11 PM'], [$daily['labels'][0], $daily['labels'][23]]);
        self::assertSame('month', $medium['granularity']);
        self::assertSame(['Aug 2026', 'Sep 2026'], $medium['labels']);
        self::assertSame('year', $long['granularity']);
        self::assertSame(['2024', '2025', '2026'], $long['labels']);
    }

    public function test_revenue_stats_widget_uses_precomputed_period_aware_overview_without_queries(): void
    {
        self::assertTrue(is_subclass_of(RevenueReportStats::class, StatsOverviewWidget::class));

        $widget = new RevenueReportStats;
        $widget->reportData = [
            'revenue' => 1250.00,
            'paymentsReceived' => 3,
            'refunds' => 100.00,
            'refundCount' => 1,
            'netRevenue' => 1150.00,
            'outstanding' => 450.00,
        ];
        $widget->reportPeriodLabel = 'Quarterly';
        $widget->drillDownUrls = [
            'revenue' => '/admin/payments?scope=revenue',
            'refunds' => '/admin/payments?scope=refunds',
            'netRevenue' => '/admin/transaction-dashboard#collection-performance',
            'outstanding' => '/admin/transaction-dashboard#transaction-breakdown',
        ];
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $stats = $method->invoke($widget);
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
        }

        self::assertCount(0, $queries);
        self::assertCount(4, $stats);
        self::assertSame(
            ['Revenue received', 'Refunds', 'Net revenue', 'Outstanding balance'],
            array_map(fn ($stat): string => $stat->getLabel(), $stats),
        );
        self::assertSame(
            ['GHS 1,250.00', 'GHS 100.00', 'GHS 1,150.00', 'GHS 450.00'],
            array_map(fn ($stat): string => $stat->getValue(), $stats),
        );
        self::assertSame('3 payment(s) in Quarterly', $stats[0]->getDescription());
        self::assertSame('1 refund(s) in Quarterly', $stats[1]->getDescription());
        self::assertSame('Unpaid transactions in Quarterly', $stats[3]->getDescription());
        self::assertSame('/admin/payments?scope=revenue', $stats[0]->getUrl());
        self::assertSame('/admin/payments?scope=refunds', $stats[1]->getUrl());
        self::assertSame('/admin/transaction-dashboard#collection-performance', $stats[2]->getUrl());
        self::assertSame('/admin/transaction-dashboard#transaction-breakdown', $stats[3]->getUrl());
    }

    public function test_revenue_report_page_calculates_the_report_only_once(): void
    {
        Role::findOrCreate('accountant', 'web');
        $accountant = User::factory()->create(['department' => 'accountant']);
        $accountant->assignRole('accountant');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            Livewire::actingAs($accountant)
                ->test(RevenueReport::class)
                ->assertSuccessful();
            $reportQueries = collect(DB::getQueryLog())
                ->pluck('query')
                ->filter(fn (string $query): bool => (bool) preg_match(
                    '/\b(?:from|join)\s+["`]?(?:payments|bookings|conference_bookings|restaurant_reservations|restaurant_orders)["`]?\b/i',
                    $query,
                ));
        } finally {
            DB::disableQueryLog();
        }

        self::assertCount(9, $reportQueries);
    }

    public function test_revenue_report_page_renders_a_card_for_every_business_channel(): void
    {
        Role::findOrCreate('accountant', 'web');
        $accountant = User::factory()->create(['department' => 'accountant']);
        $accountant->assignRole('accountant');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $response = $this->actingAs($accountant)->get(RevenueReport::getUrl());

        $response->assertOk()->assertSeeInOrder([
            'Revenue by business channel',
            'Hotel bookings',
            'Conference bookings',
            'Table reservations',
            'Food orders',
            'Other / direct',
        ]);

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $section = $xpath->query('//section[@aria-labelledby="revenue-channel-heading"]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $section);
        $cards = $xpath->query('.//article', $section);
        self::assertCount(5, $cards);

        foreach ($cards as $card) {
            self::assertStringContainsString('payment(s)', $card->textContent);
            self::assertMatchesRegularExpression('/\d+\.\d% of revenue/', $card->textContent);
        }

        $links = $xpath->query('.//a[@data-revenue-drill-down="channel"]', $section);
        self::assertCount(5, $links);

        foreach ($links as $link) {
            self::assertStringContainsString('/admin/payments', $link->getAttribute('href'));
        }
    }

    public function test_revenue_report_renders_responsive_outstanding_balance_cards_with_shares_and_drill_downs(): void
    {
        $this->travelTo('2026-09-04 12:00:00');
        [$guest, $room, $conferenceRoom, $restaurant, $table] = $this->serviceFixture();

        Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-11',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);
        ConferenceBooking::query()->create([
            'guest_id' => $guest->id,
            'conference_room_id' => $conferenceRoom->id,
            'booking_date' => '2026-10-10',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => 200,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);
        RestaurantReservation::query()->create([
            'restaurant_id' => $restaurant->id,
            'restaurant_table_id' => $table->id,
            'guest_id' => $guest->id,
            'guest_name' => $guest->full_name,
            'guest_email' => $guest->email,
            'guest_phone' => $guest->phone_number,
            'reservation_date' => '2026-10-10',
            'reservation_time' => '18:00',
            'number_of_guests' => 2,
            'reservation_fee' => 300,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);
        RestaurantOrder::query()->create([
            'guest_id' => $guest->id,
            'order_number' => 'REVENUE-OUTSTANDING-CARDS',
            'total' => 400,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);

        $accountant = $this->revenueReportUser('accountant', withTransactionDashboard: true);
        $response = $this->actingAs($accountant)->get(RevenueReport::getUrl());

        $response->assertOk()->assertSeeInOrder([
            'Outstanding by transaction',
            'Hotel bookings',
            '10.0% of outstanding',
            'Conference bookings',
            '20.0% of outstanding',
            'Table reservations',
            '30.0% of outstanding',
            'Food orders',
            '40.0% of outstanding',
            'Payment methods',
        ]);

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $section = $xpath->query('//section[@aria-labelledby="outstanding-breakdown-heading"]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $section);
        $grid = $xpath->query('.//*[@data-outstanding-grid]', $section)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $grid);
        self::assertStringContainsString('sm:grid-cols-2', $grid->getAttribute('class'));
        self::assertStringContainsString('xl:grid-cols-4', $grid->getAttribute('class'));

        $cards = $xpath->query('.//article[@data-outstanding-card]', $section);
        self::assertCount(4, $cards);
        self::assertCount(4, array_unique(array_map(
            fn (\DOMElement $card): string => $card->getAttribute('data-outstanding-tone'),
            iterator_to_array($cards),
        )));

        $links = $xpath->query('.//a[@data-revenue-drill-down="outstanding"]', $section);
        self::assertCount(4, $links);

        foreach ($links as $link) {
            self::assertSame('/admin/transaction-dashboard', parse_url($link->getAttribute('href'), PHP_URL_PATH));
            self::assertSame('transaction-breakdown', parse_url($link->getAttribute('href'), PHP_URL_FRAGMENT));
            self::assertStringContainsString('outstanding balance', $link->getAttribute('aria-label'));
        }

        $progressBars = $xpath->query('.//*[@role="progressbar"]', $section);
        self::assertCount(4, $progressBars);
        self::assertSame(
            ['10', '20', '30', '40'],
            array_map(
                fn (\DOMElement $bar): string => $bar->getAttribute('aria-valuenow'),
                iterator_to_array($progressBars),
            ),
        );
    }

    public function test_revenue_report_places_comparison_and_trend_before_detailed_financial_sections(): void
    {
        Role::findOrCreate('accountant', 'web');
        $accountant = User::factory()->create(['department' => 'accountant']);
        $accountant->assignRole('accountant');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $response = $this->actingAs($accountant)->get(RevenueReport::getUrl());

        $response->assertOk()->assertSeeInOrder([
            'Revenue by business channel',
            'Previous-period comparison',
            'Revenue trend',
            'Outstanding by transaction',
            'Payment methods',
        ]);
    }

    public function test_revenue_report_builds_filtered_payment_and_transaction_drill_downs(): void
    {
        $accountant = $this->revenueReportUser('accountant', withTransactionDashboard: true);
        $this->actingAs($accountant);

        $page = new RevenueReport;
        $page->period = 'custom';
        $page->startDate = '2026-08-10';
        $page->endDate = '2026-08-12';
        $urls = $page->drillDownUrls(['cash']);

        $revenueFilters = $this->urlFilters($urls['revenue']);
        self::assertSame('/admin/payments', parse_url($urls['revenue'], PHP_URL_PATH));
        self::assertSame('revenue', $revenueFilters['payment_status']);
        self::assertSame('created_at', $revenueFilters['date_basis']);
        self::assertSame('2026-08-10', $revenueFilters['start_date']);
        self::assertSame('2026-08-12', $revenueFilters['end_date']);

        $refundFilters = $this->urlFilters($urls['refunds']);
        self::assertSame('refunded', $refundFilters['payment_status']);
        self::assertSame('refunded_at', $refundFilters['date_basis']);
        self::assertSame('hotel_bookings', $this->urlFilters($urls['channels']['hotel'])['transaction_type']);
        self::assertSame('other', $this->urlFilters($urls['channels']['other'])['transaction_type']);
        self::assertSame('cash', $this->urlFilters($urls['methods']['cash'])['payment_method']);
        self::assertSame('/admin/transaction-dashboard', parse_url($urls['netRevenue'], PHP_URL_PATH));
        self::assertSame('collection-performance', parse_url($urls['netRevenue'], PHP_URL_FRAGMENT));
        self::assertSame('/admin/transaction-dashboard', parse_url($urls['outstanding'], PHP_URL_PATH));
        self::assertSame('transaction-breakdown', parse_url($urls['outstanding'], PHP_URL_FRAGMENT));
    }

    public function test_revenue_report_uses_authorized_transaction_fallbacks_for_managers(): void
    {
        $manager = $this->revenueReportUser('manager', withTransactionDashboard: true);
        $this->actingAs($manager);

        self::assertFalse(PaymentResource::canViewAny());
        self::assertTrue(TransactionDashboard::canAccess());

        $page = new RevenueReport;
        $page->period = 'custom';
        $page->startDate = '2026-08-10';
        $page->endDate = '2026-08-12';
        $urls = $page->drillDownUrls(['cash']);
        $financialUrls = [
            $urls['revenue'],
            $urls['refunds'],
            ...array_values($urls['channels']),
            ...array_values($urls['methods']),
        ];

        foreach ($financialUrls as $url) {
            self::assertSame('/admin/transaction-dashboard', parse_url($url, PHP_URL_PATH));
        }
    }

    public function test_revenue_report_payment_method_cards_show_distinct_shares_and_link_to_filtered_payments(): void
    {
        $this->travelTo('2026-09-04 12:00:00');
        [$guest] = $this->serviceFixture();
        $this->payment($guest, 'booking_id', null, 100, 'REVENUE-METHOD-CASH');
        $this->payment($guest, 'booking_id', null, 300, 'REVENUE-METHOD-MOMO')
            ->update(['method' => 'momo']);
        $this->payment($guest, 'booking_id', null, 600, 'REVENUE-METHOD-BANK')
            ->update(['method' => 'bank_transfer']);
        $accountant = $this->revenueReportUser('accountant', withTransactionDashboard: true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $response = $this->actingAs($accountant)->get(RevenueReport::getUrl());
        $response->assertOk()->assertSeeInOrder([
            'Payment methods',
            'Bank transfer',
            '60.0% of revenue',
            'Mobile money',
            '30.0% of revenue',
            'Cash',
            '10.0% of revenue',
        ]);

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $section = $xpath->query('//section[@aria-labelledby="payment-methods-heading"]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $section);
        $grid = $xpath->query('.//*[@data-payment-method-grid]', $section)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $grid);
        self::assertStringContainsString('sm:grid-cols-2', $grid->getAttribute('class'));
        self::assertStringContainsString('xl:grid-cols-3', $grid->getAttribute('class'));

        $cards = $xpath->query('.//article[@data-payment-method-card]', $section);
        self::assertCount(3, $cards);
        self::assertCount(3, array_unique(array_map(
            fn (\DOMElement $card): string => $card->getAttribute('data-payment-method-tone'),
            iterator_to_array($cards),
        )));

        foreach ($cards as $card) {
            $tone = $card->getAttribute('data-payment-method-tone');

            self::assertStringContainsString("border-{$tone}-200", $card->getAttribute('class'));
        }

        $links = $xpath->query('.//a[@data-revenue-drill-down="method"]', $section);
        self::assertCount(3, $links);

        foreach ($links as $link) {
            $method = $link->getAttribute('data-payment-method-key');

            self::assertSame($method, $this->urlFilters($link->getAttribute('href'))['payment_method']);
            self::assertStringContainsString('revenue payments', $link->getAttribute('aria-label'));
        }

        $progressBars = $xpath->query('.//*[@role="progressbar"]', $section);
        self::assertCount(3, $progressBars);
        self::assertSame(
            ['60', '30', '10'],
            array_map(
                fn (\DOMElement $bar): string => $bar->getAttribute('aria-valuenow'),
                iterator_to_array($progressBars),
            ),
        );
    }

    public function test_revenue_report_indexes_cover_period_and_status_predicates(): void
    {
        foreach ($this->revenueIndexDefinitions() as $table => $expectedIndexes) {
            $installedIndexes = collect(Schema::getIndexes($table))->keyBy('name');

            foreach ($expectedIndexes as $name => $columns) {
                self::assertTrue($installedIndexes->has($name), "Missing revenue report index [{$name}].");
                self::assertSame($columns, $installedIndexes->get($name)['columns']);
            }
        }
    }

    public function test_revenue_report_index_migration_is_reversible(): void
    {
        $path = database_path('migrations/2026_09_04_000300_add_revenue_report_indexes.php');

        self::assertFileExists($path);

        $migration = require $path;
        $migration->down();

        foreach ($this->revenueIndexDefinitions() as $table => $expectedIndexes) {
            foreach (array_keys($expectedIndexes) as $name) {
                self::assertNotContains($name, Schema::getIndexListing($table));
            }
        }

        $migration->up();

        foreach ($this->revenueIndexDefinitions() as $table => $expectedIndexes) {
            $installedIndexes = collect(Schema::getIndexes($table))->keyBy('name');

            foreach ($expectedIndexes as $name => $columns) {
                self::assertSame($columns, $installedIndexes->get($name)['columns'] ?? null);
            }
        }
    }

    public function test_outstanding_breakdown_uses_remaining_balances_after_successful_payments(): void
    {
        [$guest, $room, $conferenceRoom, $restaurant, $table] = $this->serviceFixture();

        $hotel = $this->createdAt(Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-11',
            'total_price' => 1000,
            'status' => 'confirmed',
            'payment_status' => 'partially_paid',
        ]), '2026-08-05 09:00:00');
        $conference = $this->createdAt(ConferenceBooking::query()->create([
            'guest_id' => $guest->id,
            'conference_room_id' => $conferenceRoom->id,
            'booking_date' => '2026-10-10',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => 800,
            'status' => 'confirmed',
            'payment_status' => 'partial',
        ]), '2026-08-10 09:00:00');
        $reservation = $this->createdAt(RestaurantReservation::query()->create([
            'restaurant_id' => $restaurant->id,
            'restaurant_table_id' => $table->id,
            'guest_id' => $guest->id,
            'guest_name' => $guest->full_name,
            'guest_email' => $guest->email,
            'guest_phone' => $guest->phone_number,
            'reservation_date' => '2026-10-10',
            'reservation_time' => '18:00',
            'number_of_guests' => 2,
            'reservation_fee' => 600,
            'status' => 'confirmed',
            'payment_status' => 'partial',
        ]), '2026-08-15 09:00:00');
        $food = $this->createdAt(RestaurantOrder::query()->create([
            'guest_id' => $guest->id,
            'order_number' => 'REVENUE-REPORT-PARTIAL',
            'total' => 400,
            'status' => 'confirmed',
            'payment_status' => 'failed',
        ]), '2026-08-20 09:00:00');
        $overpaid = $this->createdAt(Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-10-12',
            'check_out' => '2026-10-13',
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]), '2026-08-25 09:00:00');

        $this->payment($guest, 'booking_id', $hotel->id, 250, 'REVENUE-HOTEL-PARTIAL');
        $this->payment($guest, 'conference_booking_id', $conference->id, 300, 'REVENUE-CONFERENCE-PARTIAL');
        $this->payment($guest, 'restaurant_reservation_id', $reservation->id, 100, 'REVENUE-TABLE-PARTIAL');
        $this->payment($guest, 'restaurant_order_id', $food->id, 50, 'REVENUE-FOOD-PARTIAL');
        $this->payment($guest, 'booking_id', $overpaid->id, 150, 'REVENUE-HOTEL-OVERPAID');

        $reportPage = new RevenueReport;
        $reportPage->period = 'custom';
        $reportPage->startDate = '2026-08-01';
        $reportPage->endDate = '2026-08-31';
        $report = $reportPage->report();

        self::assertSame([
            'hotel' => 750.0,
            'conference' => 500.0,
            'table' => 500.0,
            'food' => 350.0,
        ], $report['outstandingBreakdown']);
        self::assertSame(2100.0, $report['outstanding']);
    }

    public function test_no_show_conference_booking_is_excluded_from_outstanding_balances(): void
    {
        [$guest, , $conferenceRoom] = $this->serviceFixture();
        $this->createdAt(ConferenceBooking::query()->create([
            'guest_id' => $guest->id,
            'conference_room_id' => $conferenceRoom->id,
            'booking_date' => '2026-10-10',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'attendees' => 10,
            'total_price' => 800,
            'status' => 'no_show',
            'payment_status' => 'pending',
        ]), '2026-08-10 09:00:00');

        $reportPage = new RevenueReport;
        $reportPage->period = 'custom';
        $reportPage->startDate = '2026-08-01';
        $reportPage->endDate = '2026-08-31';

        self::assertSame(0.0, $reportPage->report()['outstandingBreakdown']['conference']);
    }

    public function test_refund_period_does_not_rewrite_the_original_collection_period(): void
    {
        [$guest] = $this->serviceFixture();
        $payment = $this->createdAt(
            $this->payment($guest, 'booking_id', null, 100, 'REVENUE-REFUND-PERIOD'),
            '2026-07-15 09:00:00',
        );

        Carbon::setTestNow('2026-08-20 12:00:00');

        try {
            $payment->update(['payment_status' => 'refunded']);
        } finally {
            Carbon::setTestNow();
        }

        $collectionPeriod = $this->reportForPeriod('2026-07-01', '2026-07-31');
        $refundPeriod = $this->reportForPeriod('2026-08-01', '2026-08-31');

        self::assertSame(100.0, $collectionPeriod['revenue']);
        self::assertSame(0.0, $collectionPeriod['refunds']);
        self::assertSame(100.0, $collectionPeriod['netRevenue']);
        self::assertSame(1, $collectionPeriod['paymentsReceived']);
        self::assertSame(0.0, $refundPeriod['revenue']);
        self::assertSame(100.0, $refundPeriod['refunds']);
        self::assertSame(-100.0, $refundPeriod['netRevenue']);
        self::assertSame(1, $refundPeriod['refundCount']);
    }

    public function test_later_payment_edits_do_not_move_a_refund_to_another_period(): void
    {
        [$guest] = $this->serviceFixture();
        $payment = $this->createdAt(
            $this->payment($guest, 'booking_id', null, 100, 'REVENUE-REFUND-STABLE'),
            '2026-07-15 09:00:00',
        );

        Carbon::setTestNow('2026-08-20 12:00:00');
        $payment->update(['payment_status' => 'refunded']);

        Carbon::setTestNow('2026-09-10 14:00:00');

        try {
            $payment->update(['transaction_reference' => 'REVENUE-REFUND-CORRECTED']);
        } finally {
            Carbon::setTestNow();
        }

        $refundPeriod = $this->reportForPeriod('2026-08-01', '2026-08-31');
        $editPeriod = $this->reportForPeriod('2026-09-01', '2026-09-30');

        self::assertSame(100.0, $refundPeriod['refunds']);
        self::assertSame(1, $refundPeriod['refundCount']);
        self::assertSame(0.0, $editPeriod['refunds']);
        self::assertSame(0, $editPeriod['refundCount']);
    }

    /**
     * @return array{0: Guest, 1: Room, 2: ConferenceRoom, 3: Restaurant, 4: RestaurantTable}
     */
    private function serviceFixture(): array
    {
        $guest = Guest::query()->create([
            'first_name' => 'Revenue',
            'last_name' => 'Report Guest',
            'email' => 'revenue-report@example.test',
            'phone_number' => '0240000000',
        ]);
        $roomType = RoomType::query()->create([
            'name' => 'Revenue Report Room Type',
            'price_per_night' => 100,
            'capacity' => 2,
        ]);
        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'REVENUE-101',
            'status' => 'available',
        ]);
        $conferenceRoom = ConferenceRoom::query()->create([
            'name' => 'Revenue Report Conference Room',
            'capacity' => 20,
            'price_per_hour' => 100,
            'is_available' => true,
        ]);
        $restaurant = Restaurant::query()->create([
            'name' => 'Revenue Report Restaurant',
            'description' => 'Restaurant fixture for revenue report tests.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $table = RestaurantTable::query()->create([
            'restaurant_id' => $restaurant->id,
            'table_number' => 'REVENUE-T1',
            'capacity' => 4,
        ]);

        return [$guest, $room, $conferenceRoom, $restaurant, $table];
    }

    private function revenueReportUser(string $roleName, bool $withTransactionDashboard = false): User
    {
        $role = Role::findOrCreate($roleName, 'web');

        if ($withTransactionDashboard) {
            $role->givePermissionTo(Permission::findOrCreate('view transaction dashboard', 'web'));
        }

        $user = User::factory()->create();
        $user->assignRole($role);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $user;
    }

    /**
     * @return array<string, string>
     */
    private function urlFilters(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return $query['filters'] ?? [];
    }

    private function payment(
        Guest $guest,
        string $foreignKey,
        ?int $foreignId,
        float $amount,
        string $reference,
    ): Payment {
        return Payment::query()->create([
            'guest_id' => $guest->id,
            $foreignKey => $foreignId,
            'amount' => $amount,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => $reference,
        ]);
    }

    /**
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @return TModel
     */
    private function createdAt(Model $model, string $createdAt): Model
    {
        $model->forceFill([
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ])->saveQuietly();

        return $model;
    }

    /**
     * @return array<string, mixed>
     */
    private function reportForPeriod(string $startDate, string $endDate): array
    {
        $reportPage = new RevenueReport;
        $reportPage->period = 'custom';
        $reportPage->startDate = $startDate;
        $reportPage->endDate = $endDate;

        return $reportPage->report();
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    private function revenueIndexDefinitions(): array
    {
        return [
            'payments' => [
                'payments_revenue_period_status_index' => ['created_at', 'payment_status'],
            ],
            'bookings' => [
                'bookings_revenue_period_status_index' => ['created_at', 'status', 'payment_status'],
            ],
            'conference_bookings' => [
                'conference_bookings_revenue_period_status_index' => ['created_at', 'status', 'payment_status'],
            ],
            'restaurant_reservations' => [
                'restaurant_reservations_revenue_period_status_index' => ['created_at', 'status', 'payment_status'],
            ],
            'restaurant_orders' => [
                'restaurant_orders_revenue_period_status_index' => ['created_at', 'status', 'payment_status'],
            ],
        ];
    }
}
