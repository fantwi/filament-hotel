<?php

namespace Tests\Feature;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Filament\Admin\Pages\Dashboards\TimeFilteredDashboard;
use App\Filament\Admin\Pages\Dashboards\TransactionDashboard;
use App\Filament\Admin\Widgets\TransactionOverview;
use App\Filament\Admin\Widgets\TransactionStats;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use ReflectionMethod;
use Tests\TestCase;

class TransactionDashboardTest extends TestCase
{
    public function test_transaction_dashboard_uses_shared_date_filtering_and_overview_widget(): void
    {
        self::assertTrue(is_subclass_of(TransactionDashboard::class, TimeFilteredDashboard::class));

        $dashboard = new TransactionDashboard;

        self::assertSame([TransactionStats::class, TransactionOverview::class], $dashboard->getWidgets());
        self::assertTrue(is_subclass_of(TransactionStats::class, StatsOverviewWidget::class));
        self::assertContains(InteractsWithDashboardDateRange::class, class_uses_recursive(TransactionStats::class));
        self::assertSame(1, $dashboard->getColumns());
    }

    public function test_transaction_dashboard_registers_the_stats_before_the_detailed_overview(): void
    {
        $widgets = (new TransactionDashboard)->getWidgets();

        self::assertSame(TransactionStats::class, $widgets[0]);
        self::assertSame(TransactionOverview::class, $widgets[1]);
    }

    public function test_transaction_widgets_span_the_full_dashboard_width(): void
    {
        $stats = new TransactionStats;
        $overview = new TransactionOverview;

        self::assertSame('full', $stats->getColumnSpan());
        self::assertSame('full', $overview->getColumnSpan());
    }

    public function test_transaction_stats_use_balanced_responsive_columns(): void
    {
        $stats = new class extends TransactionStats
        {
            protected function getStats(): array
            {
                return [
                    Stat::make('One', 1),
                    Stat::make('Two', 2),
                    Stat::make('Three', 3),
                    Stat::make('Four', 4),
                    Stat::make('Five', 5),
                ];
            }
        };
        $method = new ReflectionMethod($stats, 'getColumns');
        $method->setAccessible(true);

        self::assertSame([
            'default' => 1,
            'md' => 2,
            'xl' => 5,
        ], $method->invoke($stats));
    }

    public function test_historical_transaction_stats_do_not_poll_automatically(): void
    {
        $method = new ReflectionMethod(new TransactionStats, 'getPollingInterval');
        $method->setAccessible(true);

        self::assertNull($method->invoke(new TransactionStats));
    }
}
