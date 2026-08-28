<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Payments\Pages\ListPayments;
use App\Filament\Admin\Widgets\PaymentReportStats;
use App\Services\PaymentReportFilters;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentReportStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_report_stats_widget_supports_type_and_period_filters(): void
    {
        self::assertTrue(is_subclass_of(PaymentReportStats::class, StatsOverviewWidget::class));

        $widget = new PaymentReportStats;
        $widget->pageFilters = [
            'transaction_type' => 'food_orders',
            'period' => 'daily',
        ];
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        $stats = $method->invoke($widget);

        self::assertCount(4, $stats);
    }

    public function test_payment_report_filter_options_include_all_supported_scopes(): void
    {
        self::assertSame(
            ['all', 'food_orders', 'conference_bookings', 'hotel_bookings', 'table_reservations'],
            array_keys(PaymentReportFilters::typeOptions()),
        );
        self::assertSame(
            ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'],
            array_keys(PaymentReportFilters::periodOptions()),
        );
    }

    public function test_payments_page_registers_the_report_widget_above_the_table(): void
    {
        $page = new ListPayments;
        $method = new \ReflectionMethod($page, 'getHeaderWidgets');
        $method->setAccessible(true);

        self::assertSame([PaymentReportStats::class], $method->invoke($page));
        self::assertTrue(is_subclass_of(PaymentReportStats::class, StatsOverviewWidget::class));
    }
}
