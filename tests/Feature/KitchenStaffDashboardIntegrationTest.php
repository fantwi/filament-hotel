<?php

namespace Tests\Feature;

use App\Enums\StaffAccountStatus;
use App\Filament\Admin\Widgets\KitchenOrderQueue;
use App\Filament\Admin\Widgets\KitchenProductionStats;
use App\Filament\Admin\Widgets\KitchenStaffStats;
use App\Filament\Admin\Widgets\KitchenStockStats;
use App\Models\RestaurantOrder;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KitchenStaffDashboardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_authorized_kitchen_staff_can_render_the_task_focused_dashboard_hierarchy(): void
    {
        $this->actingAs($this->kitchenStaff());

        $this->get('/admin/kitchen-staff-dashboard')
            ->assertOk()
            ->assertSeeInOrder([
                'Dashboard period',
                KitchenStaffStats::class,
                'Kitchen',
                KitchenOrderQueue::class,
                KitchenProductionStats::class,
            ], escape: false)
            ->assertDontSee(KitchenStockStats::class, escape: false);
    }

    public function test_live_workload_remains_current_while_served_activity_follows_the_selected_period(): void
    {
        $staff = $this->kitchenStaff();
        $olderActive = $this->order('confirmed', 'OLDER-ACTIVE', '2026-07-31 23:59:59');
        $servedInPeriod = $this->order(
            'served',
            'SERVED-IN-PERIOD',
            '2026-07-20 10:00:00',
            '2026-08-12 12:00:00',
        );
        $servedOutsidePeriod = $this->order(
            'served',
            'SERVED-OUTSIDE-PERIOD',
            '2026-07-20 11:00:00',
            '2026-07-31 12:00:00',
        );
        $filters = [
            'period' => 'custom',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ];
        $statsWidget = new KitchenStaffStats;
        $statsWidget->pageFilters = $filters;
        $stats = $this->stats($statsWidget);

        self::assertSame('1', $stats['Orders waiting to start']->getValue());
        self::assertSame('1', $stats['Orders served']->getValue());

        Livewire::actingAs($staff)
            ->test(KitchenOrderQueue::class, ['pageFilters' => $filters])
            ->assertCanSeeTableRecords([$olderActive])
            ->assertCanNotSeeTableRecords([$servedInPeriod, $servedOutsidePeriod]);
    }

    private function kitchenStaff(): User
    {
        $role = Role::findOrCreate('kitchen_staff', 'web');
        $role->syncPermissions([
            Permission::findOrCreate('view kitchen dashboard', 'web'),
            Permission::findOrCreate('manage kitchen orders', 'web'),
            Permission::findOrCreate('manage kitchen production', 'web'),
            Permission::findOrCreate('view kitchen production reports', 'web'),
            Permission::findOrCreate('view kitchen stock', 'web'),
            Permission::findOrCreate('manage kitchen stock', 'web'),
            Permission::findOrCreate('view kitchen stock movements', 'web'),
        ]);
        $staff = User::factory()->create([
            'department' => 'kitchen_staff',
            'status' => StaffAccountStatus::Active,
        ]);
        $staff->syncRoles([$role]);

        return $staff;
    }

    private function order(
        string $status,
        string $number,
        string $createdAt,
        ?string $servedAt = null,
    ): RestaurantOrder {
        return RestaurantOrder::query()->forceCreate([
            'order_number' => $number,
            'ordering_channel' => 'web',
            'subtotal' => 20,
            'total' => 20,
            'status' => $status,
            'payment_status' => 'completed',
            'served_at' => $servedAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
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
