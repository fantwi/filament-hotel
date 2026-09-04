<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\KitchenManagerStats;
use App\Filament\Admin\Widgets\KitchenProductionStats;
use App\Models\RestaurantOrder;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class KitchenManagerDashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_period_summary_widgets_do_not_poll_automatically(): void
    {
        foreach ([KitchenManagerStats::class, KitchenProductionStats::class] as $widgetClass) {
            self::assertNull(
                $this->invokeProtected(new $widgetClass, 'getPollingInterval'),
                $widgetClass,
            );
        }
    }

    public function test_manager_summary_uses_a_balanced_responsive_five_column_grid(): void
    {
        self::assertSame([
            'default' => 1,
            'md' => 2,
            'xl' => 5,
        ], $this->invokeProtected(new KitchenManagerStats, 'getColumns'));
    }

    public function test_manager_summary_uses_one_order_aggregate_and_two_activity_queries(): void
    {
        $this->order('confirmed', 'CONFIRMED-1', '2026-08-05 10:00:00');
        $this->order('confirmed', 'CONFIRMED-2', '2026-08-06 10:00:00');
        $this->order('preparing', 'PREPARING-1', '2026-08-07 10:00:00');
        $this->order('ready', 'READY-1', '2026-08-08 10:00:00');
        $this->order('confirmed', 'OUTSIDE-PERIOD', '2026-07-31 23:59:59');
        $widget = new KitchenManagerStats;
        $widget->pageFilters = [
            'period' => 'custom',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ];

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $stats = collect($this->invokeProtected($widget, 'getStats'))
                ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat->getValue()]);
            $queryCount = count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
        }

        self::assertSame('2', $stats['Orders waiting to start']);
        self::assertSame('1', $stats['Orders preparing']);
        self::assertSame('1', $stats['Orders ready to serve']);
        self::assertSame(3, $queryCount);
    }

    private function order(string $status, string $suffix, string $createdAt): void
    {
        RestaurantOrder::query()->forceCreate([
            'order_number' => 'PERFORMANCE-'.$suffix,
            'ordering_channel' => 'web',
            'subtotal' => 20,
            'total' => 20,
            'status' => $status,
            'payment_status' => 'completed',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function invokeProtected(object $target, string $methodName): mixed
    {
        $method = new ReflectionMethod($target, $methodName);
        $method->setAccessible(true);

        return $method->invoke($target);
    }
}
