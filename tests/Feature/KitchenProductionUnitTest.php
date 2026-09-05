<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\KitchenProductions\Pages\CreateKitchenProduction;
use App\Models\Ingredient;
use App\Models\KitchenStockMovement;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KitchenProductionUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_creation_uses_the_ingredient_stock_unit_instead_of_submitted_unit_text(): void
    {
        $ingredient = $this->ingredient('kg', 10);
        $menuItem = $this->menuItem();
        $page = new class extends CreateKitchenProduction
        {
            public function persist(array $data): Model
            {
                return $this->handleRecordCreation($data);
            }
        };

        $production = $page->persist([
            'menu_item_id' => $menuItem->getKey(),
            'restaurant_id' => $ingredient->restaurant_id,
            'production_date' => today()->toDateString(),
            'quantity_produced' => 4,
            'quantity_wasted' => 0,
            'ingredients' => [[
                'ingredient_id' => $ingredient->getKey(),
                'quantity_used' => 2,
                'unit' => 'g',
            ]],
        ]);

        self::assertSame('kg', $production->ingredients()->sole()->unit);
        self::assertSame('8.000', $ingredient->refresh()->current_stock);
        self::assertDatabaseHas('kitchen_stock_movements', [
            'ingredient_id' => $ingredient->getKey(),
            'type' => KitchenStockMovement::TYPE_CONSUMPTION,
            'quantity' => 2,
        ]);
    }

    public function test_create_form_shows_the_selected_ingredients_unit_as_read_only(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $staff = User::factory()->create(['department' => 'kitchen_manager']);
        $staff->givePermissionTo(Permission::findOrCreate('manage kitchen production', 'web'));
        $ingredient = $this->ingredient('kg', 10);

        Livewire::actingAs($staff)
            ->test(CreateKitchenProduction::class)
            ->fillForm([
                'ingredients' => [[
                    'ingredient_id' => $ingredient->getKey(),
                    'quantity_used' => 2,
                ]],
            ])
            ->assertFormFieldDoesNotExist('ingredients.0.unit')
            ->assertSchemaComponentExists(
                'ingredients.0.stock_unit',
                checkComponentUsing: fn ($component): bool => $component instanceof TextEntry
                    && $component->getState() === 'kg',
            );
    }

    private function ingredient(string $unit, float $stock): Ingredient
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Canonical Unit Restaurant',
            'description' => 'Kitchen production unit tests.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);

        return Ingredient::query()->create([
            'restaurant_id' => $restaurant->getKey(),
            'name' => 'Canonical Rice',
            'unit' => $unit,
            'current_stock' => $stock,
            'reorder_level' => 2,
            'unit_cost' => 5,
            'is_active' => true,
        ]);
    }

    private function menuItem(): MenuItem
    {
        $category = MenuCategory::query()->create([
            'name' => 'Canonical Unit Meals',
            'slug' => 'canonical-unit-meals',
        ]);

        return MenuItem::query()->create([
            'menu_category_id' => $category->getKey(),
            'name' => 'Canonical Jollof Rice',
            'slug' => 'canonical-jollof-rice',
            'price' => 25,
            'tracks_kitchen_production' => true,
            'production_unit' => 'portion',
            'inventory_consumption_mode' => 'production_batch',
        ]);
    }
}
