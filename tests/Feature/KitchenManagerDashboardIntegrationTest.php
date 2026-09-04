<?php

namespace Tests\Feature;

use App\Enums\StaffAccountStatus;
use App\Filament\Admin\Widgets\KitchenManagerStats;
use App\Filament\Admin\Widgets\KitchenOrderQueue;
use App\Models\RestaurantOrder;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KitchenManagerDashboardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_authenticated_manager_can_render_and_refresh_an_empty_live_queue(): void
    {
        Livewire::actingAs($this->kitchenManager())
            ->test(KitchenOrderQueue::class)
            ->assertSeeText('No active kitchen orders')
            ->assertSeeText('Only eligible orders in Confirmed, Preparing, or Ready status appear here.')
            ->assertTableEmptyStateActionsExistInOrder(['refreshQueue'])
            ->callAction(TestAction::make('refreshQueue')->table())
            ->assertOk();
    }

    public function test_period_summary_excludes_an_older_active_order_that_remains_in_the_live_queue(): void
    {
        $manager = $this->kitchenManager();
        $olderActive = $this->order('confirmed', 'OLDER-ACTIVE', '2026-07-31 23:59:59');
        $currentActive = $this->order('confirmed', 'CURRENT-ACTIVE', '2026-08-05 10:00:00');
        $currentServed = $this->order('served', 'CURRENT-SERVED', '2026-08-06 10:00:00');
        $filters = [
            'period' => 'custom',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ];
        $widget = new KitchenManagerStats;
        $widget->pageFilters = $filters;
        $stats = $this->stats($widget);

        self::assertSame('1', $stats['Orders waiting to start']->getValue());

        Livewire::actingAs($manager)
            ->test(KitchenOrderQueue::class, ['pageFilters' => $filters])
            ->assertCanSeeTableRecords([$olderActive, $currentActive])
            ->assertCanNotSeeTableRecords([$currentServed]);
    }

    private function kitchenManager(): User
    {
        $role = Role::findOrCreate('kitchen_manager', 'web');
        $role->syncPermissions([
            Permission::findOrCreate('view kitchen dashboard', 'web'),
            Permission::findOrCreate('manage kitchen orders', 'web'),
        ]);
        $manager = User::factory()->create([
            'department' => 'kitchen_manager',
            'status' => StaffAccountStatus::Active,
        ]);
        $manager->syncRoles([$role]);

        return $manager;
    }

    private function order(string $status, string $number, string $createdAt): RestaurantOrder
    {
        return RestaurantOrder::query()->forceCreate([
            'order_number' => $number,
            'ordering_channel' => 'web',
            'subtotal' => 20,
            'total' => 20,
            'status' => $status,
            'payment_status' => 'completed',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    /**
     * @return array<string, Stat>
     */
    private function stats(KitchenManagerStats $widget): array
    {
        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return collect($method->invoke($widget))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat])
            ->all();
    }
}
