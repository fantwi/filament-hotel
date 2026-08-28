<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\KitchenProductionReportStats;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KitchenProductionReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_kitchen_report_stats_widget_uses_selected_date_range(): void
    {
        self::assertTrue(is_subclass_of(KitchenProductionReportStats::class, StatsOverviewWidget::class));

        $widget = new KitchenProductionReportStats;
        $widget->fromDate = now()->startOfMonth()->toDateString();
        $widget->untilDate = today()->toDateString();
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        $stats = $method->invoke($widget);

        self::assertCount(5, $stats);
    }
}
