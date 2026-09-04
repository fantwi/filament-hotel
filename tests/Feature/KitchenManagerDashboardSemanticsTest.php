<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboards\KitchenManagerDashboard;
use App\Filament\Admin\Widgets\KitchenManagerStats;
use App\Filament\Admin\Widgets\KitchenProductionStats;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class KitchenManagerDashboardSemanticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_movement_activity_uses_a_neutral_color(): void
    {
        $stats = $this->stats(new KitchenManagerStats);

        self::assertSame('gray', $stats['Stock movements']->getColor());
    }

    public function test_shared_period_selector_describes_its_actual_date_range_behavior(): void
    {
        $dashboard = new KitchenManagerDashboard;

        self::assertSame('Period', $this->invokeProtected($dashboard, 'dashboardPeriodFilterLabel'));
        self::assertSame(
            'Sets the reporting date range; it does not group results into a time series.',
            $this->invokeProtected($dashboard, 'dashboardPeriodFilterHelpText'),
        );
    }

    public function test_low_balance_metric_explicitly_refers_to_finished_food(): void
    {
        $stats = $this->stats(new KitchenProductionStats);

        self::assertArrayHasKey('Low finished-food balances', $stats);
        self::assertArrayNotHasKey('Low-Stock Items', $stats);
    }

    /**
     * @return array<string, Stat>
     */
    private function stats(object $widget): array
    {
        $widget->pageFilters = [
            'period' => 'custom',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-15',
        ];
        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return collect($method->invoke($widget))
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
