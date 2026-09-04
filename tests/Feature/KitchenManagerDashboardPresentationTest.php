<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\KitchenManagerStats;
use App\Filament\Admin\Widgets\KitchenProductionStats;
use App\Filament\Admin\Widgets\KitchenStockStats;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class KitchenManagerDashboardPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_kitchen_stat_groups_have_distinct_headings_and_descriptions(): void
    {
        $widgets = [
            new KitchenManagerStats,
            new KitchenProductionStats,
            new KitchenStockStats,
        ];
        $headings = [];
        $descriptions = [];

        foreach ($widgets as $widget) {
            $heading = $this->invokeProtected($widget, 'getHeading');
            $description = $this->invokeProtected($widget, 'getDescription');

            self::assertIsString($heading);
            self::assertNotSame('', trim($heading));
            self::assertIsString($description);
            self::assertNotSame('', trim($description));
            $headings[] = $heading;
            $descriptions[] = $description;
        }

        self::assertCount(3, array_unique($headings));
        self::assertCount(3, array_unique($descriptions));
    }

    public function test_finished_food_metrics_have_distinct_visual_icons(): void
    {
        $widget = new KitchenProductionStats;
        $widget->pageFilters = [
            'period' => 'custom',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ];
        $stats = $this->invokeProtected($widget, 'getStats');
        $icons = array_map(
            fn (Stat $stat): ?string => $stat->getIcon(),
            $stats,
        );

        self::assertNotContains(null, $icons);
        self::assertCount(count($stats), array_unique($icons));
    }

    private function invokeProtected(object $target, string $methodName): mixed
    {
        $method = new ReflectionMethod($target, $methodName);
        $method->setAccessible(true);

        return $method->invoke($target);
    }
}
