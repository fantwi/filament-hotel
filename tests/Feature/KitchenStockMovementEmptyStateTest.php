<?php

namespace Tests\Feature;

use App\Enums\StaffAccountStatus;
use App\Filament\Admin\Resources\KitchenStockMovements\Pages\ListKitchenStockMovements;
use App\Models\Ingredient;
use App\Models\KitchenStockMovement;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KitchenStockMovementEmptyStateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_empty_ledger_explains_how_stock_movements_are_created_without_an_irrelevant_reset(): void
    {
        Livewire::actingAs($this->kitchenStaff())
            ->test(ListKitchenStockMovements::class)
            ->assertSeeText('No stock movements recorded')
            ->assertSeeText('Stock receipts, consumption, wastage, and adjustments will appear here automatically after stock activity is recorded.')
            ->assertDontSeeText('Clear search')
            ->assertDontSeeText('Reset filters');
    }

    public function test_unmatched_search_explains_the_result_and_can_restore_the_ledger(): void
    {
        $staff = $this->kitchenStaff();
        $movement = $this->movement($staff);

        Livewire::actingAs($staff)
            ->test(ListKitchenStockMovements::class)
            ->searchTable('DOES-NOT-EXIST')
            ->assertCanNotSeeTableRecords([$movement])
            ->assertSeeText('No stock movements match your search')
            ->assertSeeText('Clear the search to return to the complete stock ledger.')
            ->assertSeeText('Clear search')
            ->assertDontSeeText('Reset filters')
            ->callAction(TestAction::make('clearSearch')->table())
            ->assertSet('tableSearch', '')
            ->assertCanSeeTableRecords([$movement]);
    }

    public function test_unmatched_filters_explain_the_result_and_can_restore_the_ledger(): void
    {
        $staff = $this->kitchenStaff();
        $movement = $this->movement($staff);

        Livewire::actingAs($staff)
            ->test(ListKitchenStockMovements::class)
            ->filterTable('direction', KitchenStockMovement::DIRECTION_OUT)
            ->assertCanNotSeeTableRecords([$movement])
            ->assertSeeText('No stock movements match these filters')
            ->assertSeeText('Reset the filters to return to the complete stock ledger.')
            ->assertSeeText('Reset filters')
            ->assertDontSeeText('Clear search')
            ->callAction(TestAction::make('resetFilters')->table())
            ->assertCanSeeTableRecords([$movement]);
    }

    private function kitchenStaff(): User
    {
        $staff = User::factory()->create([
            'department' => 'kitchen_staff',
            'status' => StaffAccountStatus::Active,
        ]);
        $staff->givePermissionTo(Permission::findOrCreate('view kitchen stock movements', 'web'));

        return $staff;
    }

    private function movement(User $staff): KitchenStockMovement
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Empty State Kitchen',
            'description' => 'Kitchen used to verify stock ledger recovery actions.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $ingredient = Ingredient::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Recovery Rice',
            'unit' => 'kg',
            'current_stock' => 10,
            'reorder_level' => 2,
            'unit_cost' => 5,
            'is_active' => true,
        ]);

        return KitchenStockMovement::query()->create([
            'ingredient_id' => $ingredient->id,
            'type' => KitchenStockMovement::TYPE_RECEIPT,
            'direction' => KitchenStockMovement::DIRECTION_IN,
            'quantity' => 10,
            'balance_before' => 0,
            'balance_after' => 10,
            'performed_by' => $staff->id,
            'occurred_at' => '2026-09-06 10:00:00',
        ]);
    }
}
