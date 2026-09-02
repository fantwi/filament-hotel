<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\KitchenOrderQueue;
use App\Filament\Admin\Widgets\KitchenStockStats;
use App\Filament\Admin\Widgets\RestaurantOrderStatusChart;
use App\Filament\Admin\Widgets\RestaurantRevenueChart;
use App\Filament\Admin\Widgets\SuperAdminFinanceStats;
use App\Filament\Admin\Widgets\SuperAdminOperationsStats;
use App\Filament\Admin\Widgets\SuperAdminStats;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class SuperAdminDashboardPollingAndChartColorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_historical_super_admin_widgets_do_not_poll_automatically(): void
    {
        foreach ([
            SuperAdminStats::class,
            SuperAdminOperationsStats::class,
            SuperAdminFinanceStats::class,
            KitchenStockStats::class,
            RestaurantRevenueChart::class,
            RestaurantOrderStatusChart::class,
        ] as $widgetClass) {
            self::assertNull(
                $this->invokeProtected(new $widgetClass, 'getPollingInterval'),
                $widgetClass,
            );
        }
    }

    public function test_live_kitchen_queue_retains_its_ten_second_polling_interval(): void
    {
        $widget = new KitchenOrderQueue;
        $table = $widget->table(Table::make($widget));

        self::assertSame('10s', $table->getPollingInterval());
    }

    public function test_restaurant_revenue_metrics_use_distinct_chart_colors(): void
    {
        $data = $this->invokeProtected(new RestaurantRevenueChart, 'getData');

        self::assertSame('#D97706', $data['datasets'][0]['borderColor']);
        self::assertSame('rgba(245, 158, 11, 0.72)', $data['datasets'][0]['backgroundColor']);
        self::assertSame('#0EA5E9', $data['datasets'][1]['borderColor']);
        self::assertSame('rgba(14, 165, 233, 0.18)', $data['datasets'][1]['backgroundColor']);
        self::assertNotSame($data['datasets'][0]['borderColor'], $data['datasets'][1]['borderColor']);
    }

    public function test_restaurant_order_statuses_use_a_distinct_semantic_palette(): void
    {
        $data = $this->invokeProtected(new RestaurantOrderStatusChart, 'getData');

        self::assertSame([
            '#F59E0B',
            '#3B82F6',
            '#F97316',
            '#22C55E',
            '#14B8A6',
            '#EF4444',
        ], $data['datasets'][0]['backgroundColor']);
        self::assertCount(6, array_unique($data['datasets'][0]['backgroundColor']));
    }

    private function invokeProtected(object $target, string $methodName): mixed
    {
        $method = new ReflectionMethod($target, $methodName);
        $method->setAccessible(true);

        return $method->invoke($target);
    }
}
