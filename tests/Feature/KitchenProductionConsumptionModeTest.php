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
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KitchenProductionConsumptionModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_mode_creation_requires_actual_ingredient_usage(): void
    {
        $menuItem = $this->menuItem('production_batch');
        $restaurant = $this->restaurant();

        try {
            $this->createPage()->persist($this->productionData($menuItem, $restaurant->id));
            self::fail('A production-batch item must not be posted without ingredient usage.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('ingredients', $exception->errors());
        }

        self::assertDatabaseCount('kitchen_productions', 0);
    }

    #[DataProvider('nonBatchModes')]
    public function test_non_batch_modes_ignore_submitted_ingredient_usage(string $mode): void
    {
        $ingredient = $this->ingredient();
        $menuItem = $this->menuItem($mode);

        $production = $this->createPage()->persist([
            ...$this->productionData($menuItem, $ingredient->restaurant_id),
            'ingredients' => [[
                'ingredient_id' => $ingredient->getKey(),
                'quantity_used' => 2,
                'notes' => 'Forged hidden form payload.',
            ]],
        ]);

        self::assertSame(0, $production->ingredients()->count());
        self::assertSame('10.000', $ingredient->refresh()->current_stock);
        self::assertSame(
            0,
            KitchenStockMovement::query()->whereMorphedTo('reference', $production)->count(),
        );
    }

    public function test_form_only_shows_ingredient_entry_for_batch_mode_items(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $staff = User::factory()->create(['department' => 'kitchen_manager']);
        $staff->givePermissionTo(Permission::findOrCreate('manage kitchen production', 'web'));
        $batchItem = $this->menuItem('production_batch');
        $perOrderItem = $this->menuItem('per_order');

        Livewire::actingAs($staff)
            ->test(CreateKitchenProduction::class)
            ->fillForm(['menu_item_id' => $batchItem->getKey()])
            ->assertSchemaComponentExists(
                'ingredients',
                checkComponentUsing: fn ($component): bool => $component instanceof Repeater
                    && $component->isVisible(),
            )
            ->assertSchemaComponentExists(
                'ingredient_deduction_mode',
                checkComponentUsing: fn ($component): bool => $component instanceof Placeholder
                    && ! $component->isVisible(),
            )
            ->fillForm(['menu_item_id' => $perOrderItem->getKey()])
            ->assertSchemaComponentExists(
                'ingredients',
                checkComponentUsing: fn ($component): bool => $component instanceof Repeater
                    && ! $component->isVisible(),
            )
            ->assertSchemaComponentExists(
                'ingredient_deduction_mode',
                checkComponentUsing: fn ($component): bool => $component instanceof Placeholder
                    && $component->isVisible(),
            );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonBatchModes(): array
    {
        return [
            'per order' => ['per_order'],
            'no deduction' => ['none'],
        ];
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

    /**
     * @return array<string, mixed>
     */
    private function productionData(MenuItem $menuItem, int $restaurantId): array
    {
        return [
            'restaurant_id' => $restaurantId,
            'menu_item_id' => $menuItem->getKey(),
            'production_date' => today()->toDateString(),
            'quantity_produced' => 10,
            'quantity_wasted' => 0,
        ];
    }

    private function ingredient(): Ingredient
    {
        $restaurant = $this->restaurant();

        return Ingredient::query()->create([
            'restaurant_id' => $restaurant->getKey(),
            'name' => 'Consumption Mode Rice',
            'unit' => 'kg',
            'current_stock' => 10,
            'reorder_level' => 2,
            'unit_cost' => 5,
            'is_active' => true,
        ]);
    }

    private function restaurant(): Restaurant
    {
        return Restaurant::query()->create([
            'name' => 'Consumption Mode Restaurant '.str()->random(6),
            'description' => 'Kitchen production consumption mode tests.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
    }

    private function menuItem(string $mode): MenuItem
    {
        $category = MenuCategory::query()->create([
            'name' => 'Consumption Mode '.str()->random(8),
            'slug' => str()->random(12),
        ]);

        return MenuItem::query()->create([
            'menu_category_id' => $category->getKey(),
            'name' => 'Consumption Mode Meal '.str()->random(8),
            'slug' => str()->random(12),
            'price' => 25,
            'tracks_kitchen_production' => true,
            'production_unit' => 'portion',
            'inventory_consumption_mode' => $mode,
        ]);
    }
}
