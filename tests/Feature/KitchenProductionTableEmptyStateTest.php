<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\KitchenProductions\Pages\ListKitchenProductions;
use App\Models\KitchenProduction;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KitchenProductionTableEmptyStateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_empty_register_guides_authorized_staff_to_create_the_first_batch(): void
    {
        $staff = $this->kitchenStaff();

        Livewire::actingAs($staff)
            ->test(ListKitchenProductions::class)
            ->assertSeeText('No production batches recorded')
            ->assertSeeText('Record the first production batch to track prepared food and ingredient usage.')
            ->assertTableEmptyStateActionsExistInOrder(['create']);
    }

    public function test_unmatched_search_explains_the_result_and_can_restore_batches(): void
    {
        $staff = $this->kitchenStaff();
        $production = $this->productionBatch($staff);

        Livewire::actingAs($staff)
            ->test(ListKitchenProductions::class)
            ->searchTable('DOES-NOT-EXIST')
            ->assertCanNotSeeTableRecords([$production])
            ->assertSeeText('No production batches match your search')
            ->assertSeeText('Clear the search to return to all production batches.')
            ->assertTableEmptyStateActionsExistInOrder(['clearSearch'])
            ->callAction(TestAction::make('clearSearch')->table())
            ->assertSet('tableSearch', '')
            ->assertCanSeeTableRecords([$production]);
    }

    public function test_unmatched_filters_explain_the_result_and_can_restore_batches(): void
    {
        $staff = $this->kitchenStaff();
        $production = $this->productionBatch($staff);

        Livewire::actingAs($staff)
            ->test(ListKitchenProductions::class)
            ->filterTable('inventory_status', 'voided')
            ->assertCanNotSeeTableRecords([$production])
            ->assertSeeText('No production batches match these filters')
            ->assertSeeText('Reset the filters to return to all production batches.')
            ->assertTableEmptyStateActionsExistInOrder(['resetFilters'])
            ->callAction(TestAction::make('resetFilters')->table())
            ->assertCanSeeTableRecords([$production]);
    }

    private function kitchenStaff(): User
    {
        $staff = User::factory()->create(['department' => 'kitchen_staff']);
        $staff->givePermissionTo(Permission::findOrCreate('manage kitchen production', 'web'));

        return $staff;
    }

    private function productionBatch(User $staff): KitchenProduction
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Empty State Kitchen',
            'description' => 'Kitchen used to verify production register recovery actions.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $category = MenuCategory::query()->create([
            'name' => 'Prepared Meals',
            'slug' => 'prepared-meals',
        ]);
        $menuItem = MenuItem::query()->create([
            'menu_category_id' => $category->id,
            'name' => 'Recovery Jollof',
            'slug' => 'recovery-jollof',
            'price' => 25,
            'tracks_kitchen_production' => true,
            'production_unit' => 'portion',
            'inventory_consumption_mode' => 'none',
        ]);

        return KitchenProduction::query()->create([
            'restaurant_id' => $restaurant->id,
            'menu_item_id' => $menuItem->id,
            'produced_by' => $staff->id,
            'batch_reference' => 'KP-EMPTY-STATE-001',
            'production_date' => '2026-09-06',
            'quantity_produced' => 20,
            'quantity_wasted' => 1,
        ]);
    }
}
