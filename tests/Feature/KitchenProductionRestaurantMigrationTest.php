<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\KitchenProduction;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class KitchenProductionRestaurantMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<int, string>
     */
    protected $connectionsToTransact = [];

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_migration_backfills_safe_legacy_ownership_without_guessing_ambiguous_batches(): void
    {
        $migration = $this->restaurantScopeMigration();
        $migration->down();

        $soleRestaurant = $this->restaurant('Only Kitchen');
        $soleRestaurantProduction = $this->legacyProduction($this->menuItem('Sole Restaurant Meal'));

        $migration->up();

        self::assertSame(
            $soleRestaurant->id,
            DB::table('kitchen_productions')->where('id', $soleRestaurantProduction->id)->value('restaurant_id'),
        );

        $migration->down();
        DB::table('kitchen_production_ingredients')->delete();
        DB::table('kitchen_productions')->delete();
        DB::table('menu_item_stock_threshold_histories')->delete();
        DB::table('menu_items')->delete();
        DB::table('menu_categories')->delete();
        DB::table('ingredients')->delete();
        DB::table('restaurants')->delete();

        $expectedRestaurant = $this->restaurant('Main Kitchen');
        $this->restaurant('Pool Kitchen');
        $ingredient = $this->ingredient($expectedRestaurant);
        $ingredientProduction = $this->legacyProduction($this->menuItem('Ingredient-Owned Meal'));
        $ingredientProduction->ingredients()->create([
            'ingredient_id' => $ingredient->id,
            'quantity_used' => 2,
            'unit' => 'kg',
        ]);
        $ambiguousProduction = $this->legacyProduction($this->menuItem('Ambiguous Meal'));

        $migration->up();

        $this->assertDatabaseHas('kitchen_production_ingredients', [
            'kitchen_production_id' => $ingredientProduction->id,
            'ingredient_id' => $ingredient->id,
        ]);
        self::assertSame(
            $expectedRestaurant->id,
            DB::table('kitchen_productions')->where('id', $ingredientProduction->id)->value('restaurant_id'),
        );
        self::assertNull(
            DB::table('kitchen_productions')->where('id', $ambiguousProduction->id)->value('restaurant_id'),
        );
    }

    private function restaurantScopeMigration(): object
    {
        $path = database_path('migrations/2026_09_05_000500_add_restaurant_to_kitchen_productions_table.php');

        self::assertFileExists($path);

        return require $path;
    }

    private function restaurant(string $name): Restaurant
    {
        return Restaurant::query()->create([
            'name' => $name,
            'description' => 'Legacy restaurant migration test.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
    }

    private function ingredient(Restaurant $restaurant): Ingredient
    {
        return Ingredient::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Legacy Rice',
            'unit' => 'kg',
            'current_stock' => 10,
            'reorder_level' => 2,
            'unit_cost' => 5,
            'is_active' => true,
        ]);
    }

    private function menuItem(string $name): MenuItem
    {
        $category = MenuCategory::query()->create([
            'name' => $name.' Category',
            'slug' => str()->random(12),
        ]);

        return MenuItem::query()->create([
            'menu_category_id' => $category->id,
            'name' => $name,
            'slug' => str()->random(12),
            'price' => 25,
            'tracks_kitchen_production' => true,
            'production_unit' => 'portion',
            'inventory_consumption_mode' => 'production_batch',
        ]);
    }

    private function legacyProduction(MenuItem $menuItem): KitchenProduction
    {
        return $menuItem->kitchenProductions()->create([
            'production_date' => today(),
            'quantity_produced' => 10,
            'quantity_wasted' => 0,
        ]);
    }
}
