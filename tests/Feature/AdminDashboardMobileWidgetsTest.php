<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboards\AdminDashboard;
use App\Filament\Admin\Pages\Dashboards\KitchenManagerDashboard;
use App\Filament\Admin\Pages\Dashboards\KitchenStaffDashboard;
use App\Filament\Admin\Pages\Dashboards\ManagerDashboard;
use App\Filament\Admin\Widgets\ExecutiveKitchenQueueSummary;
use App\Filament\Admin\Widgets\KitchenOrderQueue;
use App\Filament\Admin\Widgets\RecentPayments;
use App\Models\RestaurantOrder;
use App\Models\RestaurantTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminDashboardMobileWidgetsTest extends TestCase
{
    public function test_admin_dashboard_uses_the_compact_kitchen_summary_without_removing_operational_queues(): void
    {
        $adminWidgets = (new AdminDashboard)->getWidgets();

        self::assertContains(ExecutiveKitchenQueueSummary::class, $adminWidgets);
        self::assertNotContains(KitchenOrderQueue::class, $adminWidgets);

        foreach ([
            ManagerDashboard::class,
            KitchenManagerDashboard::class,
            KitchenStaffDashboard::class,
        ] as $dashboardClass) {
            self::assertContains(
                KitchenOrderQueue::class,
                (new $dashboardClass)->getWidgets(),
                "The operational kitchen queue should remain on [{$dashboardClass}].",
            );
        }
    }

    public function test_recent_payments_keep_primary_information_visible_and_collapse_secondary_columns_on_mobile(): void
    {
        $table = $this->table(new RecentPayments);

        foreach (['transaction_guest', 'amount', 'payment_status'] as $columnName) {
            $column = $table->getColumn($columnName);

            self::assertNotNull($column);
            self::assertNull($column->getVisibleFrom(), "[{$columnName}] should remain visible on mobile.");
        }

        foreach (['transaction_reference', 'method', 'created_at'] as $columnName) {
            $column = $table->getColumn($columnName);

            self::assertNotNull($column);
            self::assertSame('md', $column->getVisibleFrom(), "[{$columnName}] should collapse on mobile.");
            self::assertTrue($column->isToggleable(), "[{$columnName}] should be user-toggleable.");
        }
    }

    public function test_kitchen_queue_keeps_workflow_information_visible_and_responsively_collapses_details(): void
    {
        $table = $this->table(new KitchenOrderQueue);

        foreach (['mobile_order_context', 'items_summary', 'status'] as $columnName) {
            $column = $table->getColumn($columnName);

            self::assertNotNull($column);
            self::assertNull($column->getVisibleFrom(), "[{$columnName}] should remain visible on mobile.");
        }

        self::assertSame('md', $table->getColumn('mobile_order_context')?->getHiddenFrom());
        self::assertSame('md', $table->getColumn('order_number')?->getVisibleFrom());

        foreach ([
            'table_display' => 'md',
            'created_at' => 'md',
            'ordering_channel' => 'lg',
            'preparedBy.name' => 'lg',
            'kitchen_notes' => 'xl',
        ] as $columnName => $breakpoint) {
            $column = $table->getColumn($columnName);

            self::assertNotNull($column);
            self::assertSame($breakpoint, $column->getVisibleFrom(), "[{$columnName}] has the wrong responsive breakpoint.");
            self::assertTrue($column->isToggleable(), "[{$columnName}] should be user-toggleable.");
        }
    }

    public function test_kitchen_queue_mobile_order_context_includes_table_and_waiting_duration(): void
    {
        Carbon::setTestNow('2026-08-10 09:00:00');

        try {
            $order = new RestaurantOrder([
                'order_number' => 'MOBILE-ORDER-1',
            ]);
            $order->forceFill(['created_at' => '2026-08-10 08:30:00']);
            $order->setRelation('table', new RestaurantTable(['table_number' => 'T12']));
            $column = $this->table(new KitchenOrderQueue)->getColumn('mobile_order_context');

            self::assertInstanceOf(TextColumn::class, $column);
            $column->record($order)->clearCachedState();
            self::assertSame('MOBILE-ORDER-1', $column->getState());
            self::assertSame('Table T12 · Waiting 30m', $column->getDescriptionBelow());
        } finally {
            Carbon::setTestNow();
        }
    }

    private function table(RecentPayments|KitchenOrderQueue $widget): Table
    {
        return $widget->table(Table::make($this->createMock(HasTable::class)));
    }
}
