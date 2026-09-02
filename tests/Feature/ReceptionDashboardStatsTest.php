<?php

namespace Tests\Feature;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Filament\Admin\Pages\Dashboards\ReceptionDashboard;
use App\Filament\Admin\Widgets\ReceptionArrivals;
use App\Filament\Admin\Widgets\ReceptionDepartures;
use App\Filament\Admin\Widgets\ReceptionDeskStats;
use App\Filament\Admin\Widgets\ReceptionStats;
use App\Filament\Admin\Widgets\RoleDashboardOverview;
use Filament\Widgets\StatsOverviewWidget;
use ReflectionMethod;
use Tests\TestCase;

class ReceptionDashboardStatsTest extends TestCase
{
    public function test_reception_dashboard_registers_actionable_stats_before_arrivals_table(): void
    {
        $widgets = (new ReceptionDashboard)->getWidgets();

        self::assertSame(RoleDashboardOverview::class, $widgets[0]);
        self::assertContains(ReceptionStats::class, $widgets);
        self::assertContains(ReceptionDeskStats::class, $widgets);
        self::assertContains(ReceptionArrivals::class, $widgets);
        self::assertContains(ReceptionDepartures::class, $widgets);
        self::assertTrue(is_subclass_of(ReceptionStats::class, StatsOverviewWidget::class));
        self::assertTrue(is_subclass_of(ReceptionDeskStats::class, StatsOverviewWidget::class));
        self::assertContains(InteractsWithDashboardDateRange::class, class_uses_recursive(ReceptionDeskStats::class));

        $statsIndex = array_search(ReceptionStats::class, $widgets, true);
        $deskIndex = array_search(ReceptionDeskStats::class, $widgets, true);
        $arrivalsIndex = array_search(ReceptionArrivals::class, $widgets, true);
        $departuresIndex = array_search(ReceptionDepartures::class, $widgets, true);

        self::assertIsInt($statsIndex);
        self::assertIsInt($deskIndex);
        self::assertIsInt($arrivalsIndex);
        self::assertIsInt($departuresIndex);
        self::assertLessThan($statsIndex, $deskIndex);
        self::assertLessThan($arrivalsIndex, $statsIndex);
        self::assertLessThan($departuresIndex, $arrivalsIndex);
    }

    public function test_reception_guidance_and_front_desk_priorities_appear_before_the_operations_section(): void
    {
        $dashboard = new ReceptionDashboard;
        $method = new ReflectionMethod($dashboard, 'dashboardWidgetLayout');
        $method->setAccessible(true);

        [$priorityWidgets, $sections] = $method->invoke($dashboard);

        self::assertSame([
            RoleDashboardOverview::class,
            ReceptionDeskStats::class,
        ], $priorityWidgets);
        self::assertSame([
            'Operations' => [
                ReceptionStats::class,
                ReceptionArrivals::class,
                ReceptionDepartures::class,
            ],
        ], $sections);
    }
}
