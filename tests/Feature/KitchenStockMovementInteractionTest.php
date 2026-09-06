<?php

namespace Tests\Feature;

use App\Enums\StaffAccountStatus;
use App\Filament\Admin\Resources\KitchenStockMovements\KitchenStockMovementResource;
use App\Filament\Admin\Resources\KitchenStockMovements\Pages\ListKitchenStockMovements;
use App\Models\Ingredient;
use App\Models\KitchenStockMovement;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KitchenStockMovementInteractionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_authorized_staff_can_open_complete_untruncated_movement_details(): void
    {
        $staff = $this->staff();
        $movement = $this->movement($staff);

        $page = Livewire::actingAs($staff)
            ->test(ListKitchenStockMovements::class)
            ->assertTableActionVisible('view', $movement)
            ->mountAction(TestAction::make('view')->table($movement));
        $schemaMethod = new ReflectionMethod($page->instance(), 'getMountedActionSchema');
        $schemaMethod->setAccessible(true);
        $schema = $schemaMethod->invoke($page->instance());

        self::assertNotNull($schema);
        self::assertSame('Regression Foods Limited', $schema->getComponent('supplier_name')->getState());
        self::assertSame('INV-REGRESSION-001', $schema->getComponent('reference_number')->getState());
        self::assertSame('Manual stock entry', $schema->getComponent('source_type')->getState());
        self::assertSame('Ama Ledger', $schema->getComponent('performedBy.name')->getState());
        self::assertSame(
            'This complete receiving note is deliberately longer than the table preview and must remain fully visible in the audit details modal.',
            $schema->getComponent('notes')->getState(),
        );
    }

    public function test_stock_movement_register_remains_read_only_at_the_resource_and_table_boundaries(): void
    {
        $staff = $this->staff();
        $movement = $this->movement($staff);

        self::assertFalse(KitchenStockMovementResource::canCreate());
        self::assertFalse(KitchenStockMovementResource::canEdit($movement));
        self::assertFalse(KitchenStockMovementResource::canDelete($movement));

        Livewire::actingAs($staff)
            ->test(ListKitchenStockMovements::class)
            ->assertTableActionExists('view', record: $movement)
            ->assertTableActionDoesNotExist('edit', record: $movement)
            ->assertTableActionDoesNotExist('delete', record: $movement);
    }

    private function staff(): User
    {
        $staff = User::factory()->create([
            'first_name' => 'Ama',
            'last_name' => 'Ledger',
            'department' => 'kitchen_staff',
            'status' => StaffAccountStatus::Active,
        ]);
        $staff->givePermissionTo(Permission::findOrCreate('view kitchen stock movements', 'web'));

        return $staff;
    }

    private function movement(User $staff): KitchenStockMovement
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Regression Kitchen',
            'description' => 'Kitchen used to protect the stock movement interaction contract.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $ingredient = Ingredient::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Regression Rice',
            'unit' => 'kg',
            'current_stock' => 25,
            'reorder_level' => 5,
            'unit_cost' => 12.5,
            'is_active' => true,
        ]);

        return KitchenStockMovement::query()->create([
            'ingredient_id' => $ingredient->id,
            'type' => KitchenStockMovement::TYPE_RECEIPT,
            'direction' => KitchenStockMovement::DIRECTION_IN,
            'quantity' => 10,
            'balance_before' => 15,
            'balance_after' => 25,
            'unit_cost' => 12.5,
            'total_cost' => 125,
            'reference_number' => 'INV-REGRESSION-001',
            'supplier_name' => 'Regression Foods Limited',
            'performed_by' => $staff->id,
            'occurred_at' => '2026-09-06 11:30:00',
            'notes' => 'This complete receiving note is deliberately longer than the table preview and must remain fully visible in the audit details modal.',
        ]);
    }
}
