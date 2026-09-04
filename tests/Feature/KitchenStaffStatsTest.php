<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\KitchenStaffStats;
use App\Models\RestaurantOrder;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class KitchenStaffStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_workload_ignores_the_reporting_period_while_served_orders_use_it(): void
    {
        $this->order('confirmed', 'OLD-WAITING', '2026-07-28 09:00:00');
        $this->order('preparing', 'OLD-PREPARING', '2026-07-29 09:00:00');
        $this->order('ready', 'OLD-READY', '2026-07-30 09:00:00');
        $this->order('confirmed', 'UNPAID-WAITING', '2026-07-31 09:00:00', paymentStatus: 'pending');
        $this->order('served', 'SERVED-IN-PERIOD', '2026-07-20 09:00:00', servedAt: '2026-08-10 12:00:00');
        $this->order('served', 'SERVED-OUTSIDE-PERIOD', '2026-07-20 09:00:00', servedAt: '2026-07-31 12:00:00');

        $widget = new KitchenStaffStats;
        $widget->pageFilters = [
            'period' => 'custom',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ];
        $stats = $this->stats($widget);

        self::assertSame('1', $stats['Orders waiting to start']->getValue());
        self::assertSame('1', $stats['Orders preparing']->getValue());
        self::assertSame('1', $stats['Orders ready to serve']->getValue());
        self::assertSame('1', $stats['Orders served']->getValue());
    }

    public function test_staff_summary_polls_every_ten_seconds(): void
    {
        self::assertSame('10s', $this->invokeProtected(new KitchenStaffStats, 'getPollingInterval'));
    }

    public function test_staff_summary_uses_one_active_workload_aggregate_and_one_served_query(): void
    {
        $this->order('confirmed', 'WAITING-ONE', '2026-07-28 09:00:00');
        $this->order('confirmed', 'WAITING-TWO', '2026-07-29 09:00:00');
        $this->order('preparing', 'PREPARING', '2026-07-30 09:00:00');
        $this->order('ready', 'READY', '2026-07-31 09:00:00');
        $this->order('served', 'SERVED', '2026-07-20 09:00:00', servedAt: '2026-08-10 12:00:00');
        $widget = new KitchenStaffStats;
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
        self::assertSame('1', $stats['Orders served']);
        self::assertSame(2, $queryCount);
    }

    private function order(
        string $status,
        string $number,
        string $createdAt,
        string $paymentStatus = 'completed',
        ?string $servedAt = null,
    ): RestaurantOrder {
        $order = RestaurantOrder::query()->create([
            'order_number' => $number,
            'ordering_channel' => 'web',
            'subtotal' => 20,
            'total' => 20,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'served_at' => $servedAt,
        ]);
        $order->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $order;
    }

    /**
     * @return array<string, Stat>
     */
    private function stats(KitchenStaffStats $widget): array
    {
        return collect($this->invokeProtected($widget, 'getStats'))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat])
            ->all();
    }

    private function invokeProtected(object $target, string $methodName): mixed
    {
        $method = new ReflectionMethod($target, $methodName);
        $method->setAccessible(true);

        return $method->invoke($target);
    }
}
