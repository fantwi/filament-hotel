<?php

namespace Tests\Feature;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Filament\Admin\Pages\Dashboards\AccountantDashboard;
use App\Filament\Admin\Widgets\AccountantPaymentActivityChart;
use App\Filament\Admin\Widgets\AccountantReceivablesStats;
use App\Filament\Admin\Widgets\AccountantStats;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use ReflectionMethod;
use Tests\TestCase;

class AccountantDashboardStatsTest extends TestCase
{
    public function test_accountant_dashboard_registers_cash_and_receivables_stats_before_detail_widgets(): void
    {
        $widgets = (new AccountantDashboard)->getWidgets();

        $cashStats = 'App\\Filament\\Admin\\Widgets\\AccountantStats';
        $receivablesStats = 'App\\Filament\\Admin\\Widgets\\AccountantReceivablesStats';
        $corporateBilling = 'App\\Filament\\Admin\\Widgets\\CorporateBillingOverview';
        $revenueChart = AccountantPaymentActivityChart::class;
        $recentPayments = 'App\\Filament\\Admin\\Widgets\\RecentPayments';

        self::assertContains($cashStats, $widgets);
        self::assertContains($receivablesStats, $widgets);
        self::assertNotContains('App\\Filament\\Admin\\Widgets\\AccountantPeriodReport', $widgets);
        self::assertTrue(is_subclass_of($cashStats, StatsOverviewWidget::class));
        self::assertTrue(is_subclass_of($receivablesStats, StatsOverviewWidget::class));
        self::assertContains(InteractsWithDashboardDateRange::class, class_uses_recursive(AccountantReceivablesStats::class));

        $cashIndex = array_search($cashStats, $widgets, true);
        $receivablesIndex = array_search($receivablesStats, $widgets, true);
        self::assertIsInt($cashIndex);
        self::assertIsInt($receivablesIndex);

        foreach ([$corporateBilling, $revenueChart, $recentPayments] as $detailWidget) {
            $detailIndex = array_search($detailWidget, $widgets, true);
            self::assertIsInt($detailIndex);
            self::assertLessThan($detailIndex, $cashIndex);
            self::assertLessThan($detailIndex, $receivablesIndex);
        }
    }

    public function test_historical_accountant_stats_do_not_poll_automatically(): void
    {
        foreach ([AccountantStats::class, AccountantReceivablesStats::class] as $widgetClass) {
            $method = new ReflectionMethod($widgetClass, 'getPollingInterval');
            $method->setAccessible(true);

            self::assertNull($method->invoke(new $widgetClass), $widgetClass);
        }
    }

    public function test_admin_panel_does_not_discover_the_obsolete_accountant_period_widget(): void
    {
        $widgets = Filament::getPanel('admin')->getWidgets();

        self::assertNotContains(
            'App\\Filament\\Admin\\Widgets\\AccountantPeriodReport',
            $widgets,
        );
        self::assertContains(AccountantStats::class, $widgets);
        self::assertContains(AccountantReceivablesStats::class, $widgets);
    }
}
