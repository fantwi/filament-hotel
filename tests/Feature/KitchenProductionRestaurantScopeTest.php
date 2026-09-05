<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\KitchenProductions\Pages\CreateKitchenProduction;
use App\Filament\Admin\Resources\KitchenProductions\Pages\ListKitchenProductions;
use App\Models\Ingredient;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KitchenProductionRestaurantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_creation_persists_the_restaurant_for_a_valid_batch(): void
    {
        $restaurant = $this->restaurant('Main Kitchen');
        $ingredient = $this->ingredient($restaurant, 'Rice');
        $menuItem = $this->menuItem();

        $production = $this->createPage()->persist($this->productionData(
            $restaurant,
            $menuItem,
            $ingredient,
        ));

        self::assertSame($restaurant->id, $production->restaurant_id);
        self::assertSame($restaurant->id, $production->restaurant->id);
        self::assertSame('8.000', $ingredient->refresh()->current_stock);
    }

    public function test_creation_rejects_an_ingredient_from_another_restaurant(): void
    {
        $selectedRestaurant = $this->restaurant('Main Kitchen');
        $otherRestaurant = $this->restaurant('Pool Kitchen');
        $otherIngredient = $this->ingredient($otherRestaurant, 'Pool Rice');
        $menuItem = $this->menuItem();

        try {
            $this->createPage()->persist($this->productionData(
                $selectedRestaurant,
                $menuItem,
                $otherIngredient,
            ));
            self::fail('An ingredient from another restaurant must not be consumed.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('ingredients', $exception->errors());
        }

        self::assertDatabaseCount('kitchen_productions', 0);
        self::assertSame('10.000', $otherIngredient->refresh()->current_stock);
    }

    public function test_creation_requires_an_explicit_restaurant(): void
    {
        $restaurant = $this->restaurant('Main Kitchen');
        $ingredient = $this->ingredient($restaurant, 'Rice');
        $menuItem = $this->menuItem();
        $data = $this->productionData($restaurant, $menuItem, $ingredient);
        unset($data['restaurant_id']);

        try {
            $this->createPage()->persist($data);
            self::fail('A new production batch must identify its restaurant.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('restaurant_id', $exception->errors());
        }

        self::assertDatabaseCount('kitchen_productions', 0);
        self::assertSame('10.000', $ingredient->refresh()->current_stock);
    }

    public function test_creation_rejects_an_inactive_ingredient_from_the_selected_restaurant(): void
    {
        $restaurant = $this->restaurant('Main Kitchen');
        $inactiveIngredient = $this->ingredient($restaurant, 'Inactive Rice', false);
        $menuItem = $this->menuItem();

        try {
            $this->createPage()->persist($this->productionData(
                $restaurant,
                $menuItem,
                $inactiveIngredient,
            ));
            self::fail('An inactive ingredient must not be consumed by a new production batch.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('ingredients', $exception->errors());
        }

        self::assertDatabaseCount('kitchen_productions', 0);
        self::assertSame('10.000', $inactiveIngredient->refresh()->current_stock);
    }

    public function test_form_only_lists_active_ingredients_from_the_selected_restaurant(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $staff = $this->kitchenStaff();
        $selectedRestaurant = $this->restaurant('Main Kitchen');
        $otherRestaurant = $this->restaurant('Pool Kitchen');
        $activeIngredient = $this->ingredient($selectedRestaurant, 'Rice');
        $inactiveIngredient = $this->ingredient($selectedRestaurant, 'Inactive Rice', false);
        $otherIngredient = $this->ingredient($otherRestaurant, 'Pool Rice');

        Livewire::actingAs($staff)
            ->test(CreateKitchenProduction::class)
            ->fillForm([
                'restaurant_id' => $selectedRestaurant->id,
                'menu_item_id' => $this->menuItem()->id,
                'ingredients' => [[]],
            ])
            ->assertSchemaComponentExists('restaurant_id')
            ->assertSchemaComponentExists(
                'ingredients.0.ingredient_id',
                checkComponentUsing: function ($component) use ($activeIngredient, $inactiveIngredient, $otherIngredient): bool {
                    if (! $component instanceof Select) {
                        return false;
                    }

                    $options = $component->getOptions();

                    return array_key_exists($activeIngredient->id, $options)
                        && ! array_key_exists($inactiveIngredient->id, $options)
                        && ! array_key_exists($otherIngredient->id, $options);
                },
            );
    }

    public function test_changing_the_restaurant_clears_selected_ingredients(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $staff = $this->kitchenStaff();
        $firstRestaurant = $this->restaurant('Main Kitchen');
        $secondRestaurant = $this->restaurant('Pool Kitchen');
        $ingredient = $this->ingredient($firstRestaurant, 'Rice');

        Livewire::actingAs($staff)
            ->test(CreateKitchenProduction::class)
            ->fillForm([
                'restaurant_id' => $firstRestaurant->id,
                'ingredients' => [[
                    'ingredient_id' => $ingredient->id,
                    'quantity_used' => 2,
                ]],
            ])
            ->set('data.restaurant_id', $secondRestaurant->id)
            ->assertFormSet(['ingredients' => []]);
    }

    public function test_form_defaults_to_the_only_restaurant(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $staff = $this->kitchenStaff();
        $restaurant = $this->restaurant('Only Kitchen');

        Livewire::actingAs($staff)
            ->test(CreateKitchenProduction::class)
            ->assertFormSet(['restaurant_id' => $restaurant->id]);
    }

    public function test_list_exposes_restaurant_context_and_filtering(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $staff = $this->kitchenStaff();

        Livewire::actingAs($staff)
            ->test(ListKitchenProductions::class)
            ->assertTableColumnExists('restaurant.name')
            ->assertTableFilterExists('restaurant_id');
    }

    public function test_restaurant_with_production_history_cannot_be_deleted(): void
    {
        $restaurant = $this->restaurant('Historical Kitchen');
        $production = $this->legacyProduction($this->menuItem(), $restaurant);

        try {
            $restaurant->delete();
            self::fail('Deleting a restaurant must not orphan its production history.');
        } catch (QueryException) {
            // The restricted foreign key protects non-Filament deletion paths.
        }

        $this->assertDatabaseHas('restaurants', ['id' => $restaurant->id]);
        $this->assertDatabaseHas('kitchen_productions', ['id' => $production->id]);
    }

    private function createPage(): object
    {
        return new class extends CreateKitchenProduction
        {
            public function persist(array $data): Model
            {
                return $this->handleRecordCreation($data);
            }
        };
    }

    private function legacyProduction(MenuItem $menuItem, Restaurant $restaurant): Model
    {
        return $menuItem->kitchenProductions()->create([
            'restaurant_id' => $restaurant->id,
            'production_date' => today(),
            'quantity_produced' => 10,
            'quantity_wasted' => 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function productionData(Restaurant $restaurant, MenuItem $menuItem, Ingredient $ingredient): array
    {
        return [
            'restaurant_id' => $restaurant->id,
            'menu_item_id' => $menuItem->id,
            'production_date' => today()->toDateString(),
            'quantity_produced' => 10,
            'quantity_wasted' => 0,
            'ingredients' => [[
                'ingredient_id' => $ingredient->id,
                'quantity_used' => 2,
            ]],
        ];
    }

    private function kitchenStaff(): User
    {
        $staff = User::factory()->create(['department' => 'kitchen_manager']);
        $staff->givePermissionTo(Permission::findOrCreate('manage kitchen production', 'web'));

        return $staff;
    }

    private function restaurant(string $name): Restaurant
    {
        return Restaurant::query()->create([
            'name' => $name,
            'description' => 'Restaurant-scoped production tests.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
    }

    private function ingredient(Restaurant $restaurant, string $name, bool $active = true): Ingredient
    {
        return Ingredient::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => $name,
            'unit' => 'kg',
            'current_stock' => 10,
            'reorder_level' => 2,
            'unit_cost' => 5,
            'is_active' => $active,
        ]);
    }

    private function menuItem(): MenuItem
    {
        $category = MenuCategory::query()->create([
            'name' => 'Scoped Meals '.str()->random(6),
            'slug' => str()->random(12),
        ]);

        return MenuItem::query()->create([
            'menu_category_id' => $category->id,
            'name' => 'Scoped Meal '.str()->random(6),
            'slug' => str()->random(12),
            'price' => 25,
            'tracks_kitchen_production' => true,
            'production_unit' => 'portion',
            'inventory_consumption_mode' => 'production_batch',
        ]);
    }
}
