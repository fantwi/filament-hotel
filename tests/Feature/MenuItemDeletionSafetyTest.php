<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\MenuItems\MenuItemResource;
use App\Filament\Admin\Resources\MenuItems\Pages\EditMenuItem;
use App\Filament\Admin\Resources\MenuItems\Pages\ListMenuItems;
use App\Models\Ingredient;
use App\Models\KitchenProduction;
use App\Models\KitchenStockMovement;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\KitchenStockService;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MenuItemDeletionSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_cannot_delete_a_menu_item_with_production_history(): void
    {
        $admin = $this->admin();
        [$menuItem] = $this->postedProductionBatch();

        $this->actingAs($admin);

        self::assertFalse(MenuItemResource::canDelete($menuItem));
    }

    public function test_admin_can_delete_an_unused_menu_item(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin);

        self::assertTrue(MenuItemResource::canDelete($this->menuItem()));
    }

    public function test_database_rejects_menu_item_deletion_without_removing_production_history(): void
    {
        [$menuItem, $production, $ingredient] = $this->postedProductionBatch();
        $productionIngredient = $production->ingredients()->sole();
        $movement = KitchenStockMovement::query()
            ->whereMorphedTo('reference', $production)
            ->sole();

        try {
            $menuItem->delete();
            self::fail('Deleting a menu item with production history should be rejected by the database.');
        } catch (QueryException) {
            // The restricted foreign key is the final safeguard for non-Filament deletes.
        }

        $this->assertDatabaseHas('menu_items', ['id' => $menuItem->id]);
        $this->assertDatabaseHas('kitchen_productions', ['id' => $production->id]);
        $this->assertDatabaseHas('kitchen_production_ingredients', ['id' => $productionIngredient->id]);
        $this->assertDatabaseHas('kitchen_stock_movements', [
            'id' => $movement->id,
            'ingredient_id' => $ingredient->id,
            'reference_type' => $production->getMorphClass(),
            'reference_id' => $production->id,
        ]);
    }

    public function test_edit_page_disables_deletion_for_a_menu_item_with_production_history(): void
    {
        $admin = $this->admin();
        [$menuItem] = $this->postedProductionBatch($admin);

        Livewire::actingAs($admin)
            ->test(EditMenuItem::class, ['record' => $menuItem->getRouteKey()])
            ->assertActionDisabled('delete');
    }

    public function test_bulk_delete_skips_menu_items_with_production_history(): void
    {
        $admin = $this->admin();
        [$protectedMenuItem] = $this->postedProductionBatch($admin);
        $unusedMenuItem = $this->menuItem($admin, 'Unused menu item');

        Livewire::actingAs($admin)
            ->test(ListMenuItems::class)
            ->callTableBulkAction('delete', [$protectedMenuItem, $unusedMenuItem]);

        $notifications = session('filament.claimed_notifications', []);

        self::assertStringContainsString(
            'production history',
            (string) collect($notifications)->pluck('body')->implode(' '),
        );
        $this->assertDatabaseHas('menu_items', ['id' => $protectedMenuItem->id]);
        $this->assertDatabaseMissing('menu_items', ['id' => $unusedMenuItem->id]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['department' => 'admin']);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    /**
     * @return array{MenuItem, KitchenProduction, Ingredient}
     */
    private function postedProductionBatch(?User $creator = null): array
    {
        $menuItem = $this->menuItem($creator);
        $restaurant = Restaurant::query()->create([
            'name' => 'History Safe Restaurant '.str()->random(6),
            'description' => 'Verifies menu-item deletion safety.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $ingredient = Ingredient::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'History Safe Rice '.str()->random(6),
            'unit' => 'kg',
            'current_stock' => 10,
            'reorder_level' => 2,
            'unit_cost' => 5,
            'is_active' => true,
        ]);
        $production = KitchenProduction::query()->create([
            'menu_item_id' => $menuItem->id,
            'production_date' => today(),
            'quantity_produced' => 10,
            'quantity_wasted' => 0,
            'produced_by' => $creator?->id,
        ]);
        $production->ingredients()->create([
            'ingredient_id' => $ingredient->id,
            'quantity_used' => 2,
            'unit' => 'kg',
        ]);
        app(KitchenStockService::class)->consumeForProduction($production);

        return [$menuItem, $production, $ingredient];
    }

    private function menuItem(?User $creator = null, ?string $name = null): MenuItem
    {
        $category = MenuCategory::query()->create([
            'name' => 'History Safe Category '.str()->random(6),
            'slug' => str()->random(12),
        ]);

        return MenuItem::query()->create([
            'menu_category_id' => $category->id,
            'name' => $name ?? 'History Safe Meal '.str()->random(6),
            'slug' => str()->random(12),
            'price' => 25,
            'is_published' => true,
            'created_by' => $creator?->id,
            'tracks_kitchen_production' => true,
            'production_unit' => 'portion',
            'inventory_consumption_mode' => 'production_batch',
        ]);
    }
}
