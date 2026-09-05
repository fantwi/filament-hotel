<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\KitchenProductions\Pages\CreateKitchenProduction;
use App\Models\Ingredient;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KitchenProductionRecipePrefillTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_quantity_uses_live_debounced_binding_for_recipe_controls(): void
    {
        $this->page()
            ->assertSchemaComponentExists(
                'quantity_produced',
                checkComponentUsing: fn ($component): bool => $component instanceof TextInput
                    && $component->isLiveDebounced()
                    && $component->getNormalizedLiveDebounce() === 500,
            );
    }

    public function test_recipe_action_scales_recipe_quantities_for_the_production_batch(): void
    {
        $restaurant = $this->restaurant('Main Kitchen');
        $rice = $this->ingredient($restaurant, 'Rice', 'kg', 100);
        $oil = $this->ingredient($restaurant, 'Oil', 'litre', 20);
        $menuItem = $this->menuItem();
        $menuItem->recipeIngredients()->createMany([
            ['ingredient_id' => $rice->id, 'quantity_per_item' => 0.125, 'notes' => 'Washed rice'],
            ['ingredient_id' => $oil->id, 'quantity_per_item' => 0.010, 'notes' => 'Cooking oil'],
        ]);

        $this->page()
            ->fillForm([
                'restaurant_id' => $restaurant->id,
                'menu_item_id' => $menuItem->id,
                'quantity_produced' => 80,
            ])
            ->assertSchemaComponentExists(
                'recipe_prefill_status',
                checkComponentUsing: fn ($component): bool => $component instanceof Placeholder
                    && $component->getContent() === 'The configured recipe has 2 ingredients. Loading it replaces the current rows; review the estimate against actual usage.',
            )
            ->callAction($this->recipeAction())
            ->assertSet(
                'data.ingredients',
                fn (array $state): bool => $this->ingredientsMatch($state, [
                    [$rice->id, 10.000, 'Washed rice'],
                    [$oil->id, 0.800, 'Cooking oil'],
                ]),
            );
    }

    public function test_recipe_action_waits_for_confirmation_before_replacing_manual_rows(): void
    {
        $restaurant = $this->restaurant('Main Kitchen');
        $manualIngredient = $this->ingredient($restaurant, 'Spice', 'kg', 10);
        $recipeIngredient = $this->ingredient($restaurant, 'Rice', 'kg', 100);
        $menuItem = $this->menuItem();
        $menuItem->recipeIngredients()->create([
            'ingredient_id' => $recipeIngredient->id,
            'quantity_per_item' => 0.5,
        ]);

        $page = $this->page()->fillForm([
            'restaurant_id' => $restaurant->id,
            'menu_item_id' => $menuItem->id,
            'quantity_produced' => 10,
            'ingredients' => [[
                'ingredient_id' => $manualIngredient->id,
                'quantity_used' => 1.25,
                'notes' => 'Actual measured use',
            ]],
        ]);

        $page
            ->mountAction($this->recipeAction())
            ->assertSet(
                'data.ingredients',
                fn (array $state): bool => $this->ingredientsMatch($state, [
                    [$manualIngredient->id, 1.250, 'Actual measured use'],
                ]),
            )
            ->callMountedAction()
            ->assertSet(
                'data.ingredients',
                fn (array $state): bool => $this->ingredientsMatch($state, [
                    [$recipeIngredient->id, 5.000, null],
                ]),
            );
    }

    public function test_changing_production_quantity_does_not_overwrite_staff_adjusted_usage(): void
    {
        $restaurant = $this->restaurant('Main Kitchen');
        $ingredient = $this->ingredient($restaurant, 'Rice', 'kg', 100);
        $menuItem = $this->menuItem();
        $menuItem->recipeIngredients()->create([
            'ingredient_id' => $ingredient->id,
            'quantity_per_item' => 0.5,
        ]);

        $page = $this->page()
            ->fillForm([
                'restaurant_id' => $restaurant->id,
                'menu_item_id' => $menuItem->id,
                'quantity_produced' => 10,
            ])
            ->callAction($this->recipeAction())
            ->fillForm([
                'ingredients' => [[
                    'ingredient_id' => $ingredient->id,
                    'quantity_used' => 4.75,
                ]],
            ])
            ->fillForm(['quantity_produced' => 20])
            ->assertSet(
                'data.ingredients',
                fn (array $state): bool => $this->ingredientsMatch($state, [
                    [$ingredient->id, 4.750, null],
                ]),
            );
    }

    public function test_recipe_action_rejects_ingredients_from_another_restaurant_without_replacing_manual_rows(): void
    {
        $selectedRestaurant = $this->restaurant('Main Kitchen');
        $otherRestaurant = $this->restaurant('Pool Kitchen');
        $manualIngredient = $this->ingredient($selectedRestaurant, 'Spice', 'kg', 10);
        $otherIngredient = $this->ingredient($otherRestaurant, 'Pool Rice', 'kg', 100);
        $menuItem = $this->menuItem();
        $menuItem->recipeIngredients()->create([
            'ingredient_id' => $otherIngredient->id,
            'quantity_per_item' => 0.5,
        ]);

        $this->page()
            ->fillForm([
                'restaurant_id' => $selectedRestaurant->id,
                'menu_item_id' => $menuItem->id,
                'quantity_produced' => 10,
                'ingredients' => [[
                    'ingredient_id' => $manualIngredient->id,
                    'quantity_used' => 1,
                ]],
            ])
            ->callAction($this->recipeAction())
            ->assertNotified('Recipe cannot be loaded')
            ->assertSet(
                'data.ingredients',
                fn (array $state): bool => $this->ingredientsMatch($state, [
                    [$manualIngredient->id, 1.000, null],
                ]),
            );
    }

    public function test_recipe_action_rejects_inactive_ingredients_without_replacing_manual_rows(): void
    {
        $restaurant = $this->restaurant('Main Kitchen');
        $manualIngredient = $this->ingredient($restaurant, 'Spice', 'kg', 10);
        $inactiveIngredient = $this->ingredient($restaurant, 'Old Rice', 'kg', 100, false);
        $menuItem = $this->menuItem();
        $menuItem->recipeIngredients()->create([
            'ingredient_id' => $inactiveIngredient->id,
            'quantity_per_item' => 0.5,
        ]);

        $this->page()
            ->fillForm([
                'restaurant_id' => $restaurant->id,
                'menu_item_id' => $menuItem->id,
                'quantity_produced' => 10,
                'ingredients' => [[
                    'ingredient_id' => $manualIngredient->id,
                    'quantity_used' => 1,
                ]],
            ])
            ->callAction($this->recipeAction())
            ->assertNotified('Recipe cannot be loaded')
            ->assertSet(
                'data.ingredients',
                fn (array $state): bool => $this->ingredientsMatch($state, [
                    [$manualIngredient->id, 1.000, null],
                ]),
            );
    }

    public function test_recipe_action_rejects_an_estimate_below_ledger_precision_without_replacing_manual_rows(): void
    {
        $restaurant = $this->restaurant('Main Kitchen');
        $manualIngredient = $this->ingredient($restaurant, 'Spice', 'kg', 10);
        $recipeIngredient = $this->ingredient($restaurant, 'Saffron', 'kg', 10);
        $menuItem = $this->menuItem();
        $menuItem->recipeIngredients()->create([
            'ingredient_id' => $recipeIngredient->id,
            'quantity_per_item' => 0.001,
        ]);

        $this->page()
            ->fillForm([
                'restaurant_id' => $restaurant->id,
                'menu_item_id' => $menuItem->id,
                'quantity_produced' => 0.001,
                'ingredients' => [[
                    'ingredient_id' => $manualIngredient->id,
                    'quantity_used' => 0.5,
                ]],
            ])
            ->callAction($this->recipeAction())
            ->assertNotified('Recipe cannot be loaded')
            ->assertSet(
                'data.ingredients',
                fn (array $state): bool => $this->ingredientsMatch($state, [
                    [$manualIngredient->id, 0.500, null],
                ]),
            );
    }

    public function test_loaded_recipe_can_be_adjusted_and_saved_with_ledger_notes_and_stock_movement(): void
    {
        $restaurant = $this->restaurant('Main Kitchen');
        $ingredient = $this->ingredient($restaurant, 'Rice', 'kg', 100);
        $menuItem = $this->menuItem();
        $recipeNotes = str_repeat('Measured recipe note. ', 15);
        $menuItem->recipeIngredients()->create([
            'ingredient_id' => $ingredient->id,
            'quantity_per_item' => 0.5,
            'notes' => $recipeNotes,
        ]);

        $page = $this->page()
            ->fillForm([
                'restaurant_id' => $restaurant->id,
                'menu_item_id' => $menuItem->id,
                'production_date' => today()->toDateString(),
                'quantity_produced' => 10,
                'quantity_wasted' => 0,
            ])
            ->callAction($this->recipeAction())
            ->assertSet(
                'data.ingredients',
                fn (array $state): bool => $this->ingredientsMatch($state, [
                    [$ingredient->id, 5.000, $recipeNotes],
                ]),
            );

        $ingredientRowKey = array_key_first($page->get('data.ingredients'));

        $page
            ->set("data.ingredients.{$ingredientRowKey}.quantity_used", 4.75)
            ->call('create')
            ->assertHasNoFormErrors();

        $production = $menuItem->kitchenProductions()->sole();

        self::assertSame($recipeNotes, $production->ingredients()->sole()->notes);
        self::assertSame('95.250', $ingredient->refresh()->current_stock);
        self::assertDatabaseHas('kitchen_stock_movements', [
            'ingredient_id' => $ingredient->id,
            'type' => 'consumption',
            'quantity' => 4.75,
            'reference_type' => $production->getMorphClass(),
            'reference_id' => $production->id,
        ]);
    }

    public function test_form_explains_when_the_selected_batch_item_has_no_recipe(): void
    {
        $restaurant = $this->restaurant('Main Kitchen');
        $menuItem = $this->menuItem();

        $this->page()
            ->fillForm([
                'restaurant_id' => $restaurant->id,
                'menu_item_id' => $menuItem->id,
                'quantity_produced' => 10,
            ])
            ->assertSchemaComponentExists(
                'recipe_prefill_status',
                checkComponentUsing: fn ($component): bool => $component instanceof Placeholder
                    && $component->getContent() === 'No recipe ingredients are configured for this menu item. Add its recipe from Menu Items, or enter actual usage manually.',
            )
            ->assertActionDisabled($this->recipeAction());
    }

    private function page(): Testable
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $staff = User::factory()->create(['department' => 'kitchen_manager']);
        $staff->givePermissionTo(Permission::findOrCreate('manage kitchen production', 'web'));

        return Livewire::actingAs($staff)->test(CreateKitchenProduction::class);
    }

    private function recipeAction(): TestAction
    {
        return TestAction::make('loadRecipeEstimate')->schemaComponent('recipe_estimate_actions');
    }

    /**
     * @param  array<mixed>  $state
     * @param  array<int, array{int, float, ?string}>  $expected
     */
    private function ingredientsMatch(array $state, array $expected): bool
    {
        $actual = collect(array_values($state))
            ->map(fn (array $row): array => [
                (int) ($row['ingredient_id'] ?? 0),
                round((float) ($row['quantity_used'] ?? 0), 3),
                $row['notes'] ?? null,
            ])
            ->sortBy(fn (array $row): int => $row[0])
            ->values()
            ->all();

        $expected = collect($expected)
            ->sortBy(fn (array $row): int => $row[0])
            ->values()
            ->all();

        return $actual === $expected;
    }

    private function restaurant(string $name): Restaurant
    {
        return Restaurant::query()->create([
            'name' => $name,
            'description' => 'Recipe prefill tests.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
    }

    private function ingredient(Restaurant $restaurant, string $name, string $unit, float $stock, bool $active = true): Ingredient
    {
        return Ingredient::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => $name,
            'unit' => $unit,
            'current_stock' => $stock,
            'reorder_level' => 2,
            'unit_cost' => 5,
            'is_active' => $active,
        ]);
    }

    private function menuItem(): MenuItem
    {
        $category = MenuCategory::query()->create([
            'name' => 'Recipe Meals '.str()->random(6),
            'slug' => str()->random(12),
        ]);

        return MenuItem::query()->create([
            'menu_category_id' => $category->id,
            'name' => 'Recipe Meal '.str()->random(6),
            'slug' => str()->random(12),
            'price' => 25,
            'tracks_kitchen_production' => true,
            'production_unit' => 'portion',
            'inventory_consumption_mode' => 'production_batch',
        ]);
    }
}
