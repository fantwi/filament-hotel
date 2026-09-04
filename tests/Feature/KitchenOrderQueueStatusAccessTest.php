<?php

namespace Tests\Feature;

use App\Enums\StaffAccountStatus;
use App\Filament\Admin\Widgets\KitchenOrderQueue;
use App\Models\Ingredient;
use App\Models\KitchenStockMovement;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\RestaurantOrder;
use App\Models\User;
use App\Services\RestaurantKitchenService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class KitchenOrderQueueStatusAccessTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('kitchenRoles')]
    public function test_on_leave_kitchen_staff_can_read_the_queue_but_operational_actions_are_hidden(
        string $role,
    ): void {
        $staff = $this->kitchenStaff($role, StaffAccountStatus::OnLeave);
        [$confirmed] = $this->orderFixture('confirmed');
        [$preparing] = $this->orderFixture('preparing');
        [$ready] = $this->orderFixture('ready');

        $this->queueFor($staff)
            ->assertCanSeeTableRecords([$confirmed, $preparing, $ready])
            ->assertTableActionHidden('start_preparing', $confirmed)
            ->assertTableActionHidden('ready', $preparing)
            ->assertTableActionHidden('served', $ready);
    }

    #[DataProvider('kitchenQueueActions')]
    public function test_action_mounted_while_active_is_forbidden_after_kitchen_staff_go_on_leave(
        string $role,
        string $action,
        string $initialStatus,
    ): void {
        $staff = $this->kitchenStaff($role, StaffAccountStatus::Active);
        [$order, $ingredient] = $this->orderFixture($initialStatus);
        $originalOrderState = $order->refresh()->getRawOriginal();
        $originalIngredientState = $ingredient->refresh()->getRawOriginal();
        $queue = $this->queueFor($staff)
            ->assertTableActionVisible($action, $order)
            ->mountTableAction($action, $order);

        $staff->update(['status' => StaffAccountStatus::OnLeave]);

        $queue->callMountedTableAction();

        self::assertSame($initialStatus, $order->fresh()->status);
        self::assertNull($order->fresh()->stock_deducted_at);
        self::assertSame('10.000', $ingredient->fresh()->current_stock);
        self::assertSame($originalOrderState, $order->fresh()->getRawOriginal());
        self::assertSame($originalIngredientState, $ingredient->fresh()->getRawOriginal());
        self::assertSame(0, KitchenStockMovement::query()->count());
        $queue->assertForbidden();
    }

    public function test_active_kitchen_staff_can_still_prepare_an_order_and_consume_stock(): void
    {
        $staff = $this->kitchenStaff('kitchen_staff', StaffAccountStatus::Active);
        [$order, $ingredient] = $this->orderFixture('confirmed');

        $this->queueFor($staff)
            ->callTableAction('start_preparing', $order, [
                'kitchen_notes' => 'Active staff preparation',
            ])
            ->assertOk();

        self::assertSame('preparing', $order->fresh()->status);
        self::assertSame($staff->getKey(), $order->fresh()->prepared_by);
        self::assertNotNull($order->fresh()->stock_deducted_at);
        self::assertSame('7.500', $ingredient->fresh()->current_stock);
        self::assertSame(1, KitchenStockMovement::query()->count());
    }

    public function test_active_view_only_kitchen_staff_can_read_the_queue_but_operational_actions_are_hidden(): void
    {
        $staff = $this->kitchenStaff('kitchen_staff', StaffAccountStatus::Active, canManageOrders: false);
        [$confirmed] = $this->orderFixture('confirmed');
        [$preparing] = $this->orderFixture('preparing');
        [$ready] = $this->orderFixture('ready');

        $this->queueFor($staff)
            ->assertCanSeeTableRecords([$confirmed, $preparing, $ready])
            ->assertTableActionHidden('start_preparing', $confirmed)
            ->assertTableActionHidden('ready', $preparing)
            ->assertTableActionHidden('served', $ready);
    }

    public function test_live_queue_keeps_active_orders_created_before_the_dashboard_period_visible(): void
    {
        $staff = $this->kitchenStaff('kitchen_manager', StaffAccountStatus::Active);
        [$olderActiveOrder] = $this->orderFixture('confirmed');
        $olderActiveOrder->forceFill([
            'created_at' => '2026-07-31 23:59:59',
            'updated_at' => '2026-07-31 23:59:59',
        ])->saveQuietly();

        Livewire::actingAs($staff)
            ->test(KitchenOrderQueue::class, [
                'pageFilters' => [
                    'period' => 'custom',
                    'start_date' => '2026-08-01',
                    'end_date' => '2026-08-31',
                ],
            ])
            ->assertCanSeeTableRecords([$olderActiveOrder]);
    }

    #[DataProvider('kitchenServiceActions')]
    public function test_kitchen_service_rejects_on_leave_staff_before_any_transition(
        string $method,
        string $initialStatus,
    ): void {
        $staff = $this->kitchenStaff('kitchen_staff', StaffAccountStatus::OnLeave);
        [$order, $ingredient] = $this->orderFixture($initialStatus);
        $originalOrderState = $order->refresh()->getRawOriginal();
        $originalIngredientState = $ingredient->refresh()->getRawOriginal();
        $this->actingAs($staff);

        try {
            app(RestaurantKitchenService::class)->{$method}($order);
            self::fail("Expected [{$method}] to reject an on-leave staff account.");
        } catch (HttpException $exception) {
            self::assertSame(403, $exception->getStatusCode());
        }

        self::assertSame($initialStatus, $order->fresh()->status);
        self::assertNull($order->fresh()->stock_deducted_at);
        self::assertSame('10.000', $ingredient->fresh()->current_stock);
        self::assertSame($originalOrderState, $order->fresh()->getRawOriginal());
        self::assertSame($originalIngredientState, $ingredient->fresh()->getRawOriginal());
        self::assertSame(0, KitchenStockMovement::query()->count());
    }

    #[DataProvider('kitchenServiceActions')]
    public function test_kitchen_service_rejects_active_view_only_staff_before_any_transition(
        string $method,
        string $initialStatus,
    ): void {
        $staff = $this->kitchenStaff('kitchen_staff', StaffAccountStatus::Active, canManageOrders: false);
        [$order, $ingredient] = $this->orderFixture($initialStatus);
        $originalOrderState = $order->refresh()->getRawOriginal();
        $originalIngredientState = $ingredient->refresh()->getRawOriginal();
        $this->actingAs($staff);

        try {
            app(RestaurantKitchenService::class)->{$method}($order);
            self::fail("Expected [{$method}] to reject a view-only kitchen staff account.");
        } catch (HttpException $exception) {
            self::assertSame(403, $exception->getStatusCode());
        }

        self::assertSame($initialStatus, $order->fresh()->status);
        self::assertNull($order->fresh()->stock_deducted_at);
        self::assertSame('10.000', $ingredient->fresh()->current_stock);
        self::assertSame($originalOrderState, $order->fresh()->getRawOriginal());
        self::assertSame($originalIngredientState, $ingredient->fresh()->getRawOriginal());
        self::assertSame(0, KitchenStockMovement::query()->count());
    }

    public static function kitchenRoles(): array
    {
        return [
            'kitchen manager' => ['kitchen_manager'],
            'kitchen staff' => ['kitchen_staff'],
        ];
    }

    public static function kitchenQueueActions(): array
    {
        return [
            'kitchen manager prepare' => ['kitchen_manager', 'start_preparing', 'confirmed'],
            'kitchen manager ready' => ['kitchen_manager', 'ready', 'preparing'],
            'kitchen manager served' => ['kitchen_manager', 'served', 'ready'],
            'kitchen staff prepare' => ['kitchen_staff', 'start_preparing', 'confirmed'],
            'kitchen staff ready' => ['kitchen_staff', 'ready', 'preparing'],
            'kitchen staff served' => ['kitchen_staff', 'served', 'ready'],
        ];
    }

    public static function kitchenServiceActions(): array
    {
        return [
            'prepare' => ['startPreparing', 'confirmed'],
            'ready' => ['markReady', 'preparing'],
            'served' => ['markServed', 'ready'],
        ];
    }

    private function kitchenStaff(
        string $role,
        StaffAccountStatus $status,
        bool $canManageOrders = true,
    ): User {
        $staff = User::factory()->create([
            'department' => $role,
            'status' => $status,
        ]);
        $staff->syncRoles([$role]);
        $staff->roles()->firstOrFail()->givePermissionTo(
            Permission::findOrCreate('view kitchen dashboard', 'web'),
        );

        if ($canManageOrders) {
            $staff->roles()->firstOrFail()->givePermissionTo(
                Permission::findOrCreate('manage kitchen orders', 'web'),
            );
        }

        return $staff;
    }

    /**
     * @return array{RestaurantOrder, Ingredient}
     */
    private function orderFixture(string $status): array
    {
        $restaurant = Restaurant::create([
            'name' => 'Queue Test Restaurant '.str()->random(8),
            'description' => 'Restaurant used to verify kitchen queue status access.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $ingredient = Ingredient::create([
            'restaurant_id' => $restaurant->getKey(),
            'name' => 'Queue Ingredient '.str()->random(8),
            'unit' => 'kg',
            'current_stock' => 10,
            'reorder_level' => 2,
            'unit_cost' => 5,
            'is_active' => true,
        ]);
        $category = MenuCategory::create([
            'name' => 'Queue Category '.str()->random(8),
            'slug' => str()->random(12),
        ]);
        $menuItem = MenuItem::create([
            'menu_category_id' => $category->getKey(),
            'name' => 'Queue Meal '.str()->random(8),
            'slug' => str()->random(12),
            'price' => 10,
            'inventory_consumption_mode' => 'per_order',
        ]);
        $order = RestaurantOrder::create([
            'order_number' => 'QUEUE-'.str()->upper(str()->random(10)),
            'ordering_channel' => 'web',
            'subtotal' => 20,
            'total' => 20,
            'payment_status' => 'completed',
            'status' => $status,
        ]);
        $order->items()->create([
            'menu_item_id' => $menuItem->getKey(),
            'item_name' => $menuItem->name,
            'quantity' => 2,
            'unit_price' => 10,
            'total_price' => 20,
            'ingredient_usage_snapshot' => [[
                'ingredient_id' => $ingredient->getKey(),
                'quantity_per_item' => 1.25,
                'consumption_mode' => 'per_order',
            ]],
        ]);

        return [$order, $ingredient];
    }

    private function queueFor(User $staff): Testable
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return Livewire::actingAs($staff)->test(KitchenOrderQueue::class);
    }
}
