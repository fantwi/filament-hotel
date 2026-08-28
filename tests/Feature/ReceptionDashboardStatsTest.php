<?php

namespace Tests\Feature;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Filament\Admin\Pages\Dashboards\ReceptionDashboard;
use App\Filament\Admin\Widgets\ReceptionDeskStats;
use App\Filament\Admin\Widgets\ReceptionStats;
use Filament\Widgets\StatsOverviewWidget;
use Tests\TestCase;

class ReceptionDashboardStatsTest extends TestCase
{
    public function test_reception_dashboard_registers_actionable_stats_before_arrivals_table(): void
    {
        $widgets = (new ReceptionDashboard)->getWidgets();

        self::assertSame('App\\Filament\\Admin\\Widgets\\RoleDashboardOverview', $widgets[0]);
        self::assertContains(ReceptionStats::class, $widgets);
        self::assertContains(ReceptionDeskStats::class, $widgets);
        self::assertContains('App\\Filament\\Admin\\Widgets\\ReceptionArrivals', $widgets);
        self::assertTrue(is_subclass_of(ReceptionStats::class, StatsOverviewWidget::class));
        self::assertTrue(is_subclass_of(ReceptionDeskStats::class, StatsOverviewWidget::class));
        self::assertContains(InteractsWithDashboardDateRange::class, class_uses_recursive(ReceptionDeskStats::class));

        $statsIndex = array_search(ReceptionStats::class, $widgets, true);
        $deskIndex = array_search(ReceptionDeskStats::class, $widgets, true);
        $arrivalsIndex = array_search('App\\Filament\\Admin\\Widgets\\ReceptionArrivals', $widgets, true);

        self::assertIsInt($statsIndex);
        self::assertIsInt($deskIndex);
        self::assertIsInt($arrivalsIndex);
        self::assertLessThan($deskIndex, $statsIndex);
        self::assertLessThan($arrivalsIndex, $deskIndex);
    }
}
