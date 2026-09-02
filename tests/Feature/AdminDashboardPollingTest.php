<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\AdminFinanceStats;
use App\Filament\Admin\Widgets\AdminServiceStats;
use App\Filament\Admin\Widgets\KitchenOrderQueue;
use App\Filament\Admin\Widgets\ManagerOperationsChart;
use Filament\Tables\Table;
use ReflectionMethod;
use Tests\TestCase;

class AdminDashboardPollingTest extends TestCase
{
    public function test_historical_admin_widgets_do_not_poll_automatically(): void
    {
        foreach ([
            AdminServiceStats::class,
            AdminFinanceStats::class,
            ManagerOperationsChart::class,
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

    private function invokeProtected(object $target, string $methodName): mixed
    {
        $method = new ReflectionMethod($target, $methodName);
        $method->setAccessible(true);

        return $method->invoke($target);
    }
}
