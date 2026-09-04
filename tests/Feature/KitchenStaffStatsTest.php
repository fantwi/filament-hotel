<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\KitchenStaffStats;
use App\Models\RestaurantOrder;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return collect($method->invoke($widget))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat])
            ->all();
    }
}
