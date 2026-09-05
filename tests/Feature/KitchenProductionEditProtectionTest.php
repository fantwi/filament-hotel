<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\KitchenProductions\Pages\EditKitchenProduction;
use App\Models\Ingredient;
use App\Models\KitchenProduction;
use App\Models\KitchenStockMovement;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\KitchenStockService;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KitchenProductionEditProtectionTest extends TestCase
{
    use RefreshDatabase;

    private User $kitchenManager;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->kitchenManager = User::factory()->create(['department' => 'kitchen_manager']);
        $this->kitchenManager->givePermissionTo(Permission::findOrCreate('manage kitchen production', 'web'));
    }

    public function test_inventory_controlled_fields_are_disabled_when_editing_a_saved_batch(): void
    {
        ['production' => $production] = $this->productionBatch();

        Livewire::actingAs($this->kitchenManager)
            ->test(EditKitchenProduction::class, ['record' => $production->getKey()])
            ->assertFormFieldDisabled('menu_item_id')
            ->assertFormFieldDisabled('production_date')
            ->assertFormFieldDisabled('quantity_produced')
            ->assertFormFieldDisabled('quantity_wasted')
            ->assertFormFieldEnabled('notes')
            ->assertSee('Inventory-controlled fields are locked');
    }

    public function test_editing_a_batch_updates_only_notes_and_preserves_the_stock_ledger(): void
    {
        $fixture = $this->productionBatch();
        $replacementMenuItem = $this->menuItem('Replacement Meal');

        Livewire::actingAs($this->kitchenManager)
            ->test(EditKitchenProduction::class, ['record' => $fixture['production']->getKey()])
            ->fillForm([
                'menu_item_id' => $replacementMenuItem->getKey(),
                'production_date' => today()->subDay()->toDateString(),
                'quantity_produced' => 99,
                'quantity_wasted' => 9,
                'notes' => 'Corrected production note.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $production = $fixture['production']->refresh();

        self::assertSame($fixture['menuItem']->getKey(), $production->menu_item_id);
        self::assertSame(today()->subDays(2)->toDateString(), $production->production_date->toDateString());
        self::assertSame('10.000', $production->quantity_produced);
        self::assertSame('1.000', $production->quantity_wasted);
        self::assertSame('Corrected production note.', $production->notes);
        self::assertSame('8.000', $fixture['ingredient']->refresh()->current_stock);
        self::assertSame(
            1,
            KitchenStockMovement::query()
                ->whereMorphedTo('reference', $production)
                ->where('type', KitchenStockMovement::TYPE_CONSUMPTION)
                ->count(),
        );
    }

    public function test_edit_page_displays_the_recorded_ingredient_consumption_read_only(): void
    {
        ['production' => $production] = $this->productionBatch();

        Livewire::actingAs($this->kitchenManager)
            ->test(EditKitchenProduction::class, ['record' => $production->getKey()])
            ->assertSee('Recorded ingredients consumed')
            ->assertSee('Audit Rice')
            ->assertSee('2.000 kg');
    }

    public function test_server_update_whitelists_notes_when_request_data_is_tampered(): void
    {
        $fixture = $this->productionBatch();
        $replacementMenuItem = $this->menuItem('Tampered Replacement Meal');
        $page = new class extends EditKitchenProduction
        {
            public function persistTamperedData(Model $record, array $data): Model
            {
                return $this->handleRecordUpdate($record, $data);
            }
        };

        $page->persistTamperedData($fixture['production'], [
            'menu_item_id' => $replacementMenuItem->getKey(),
            'production_date' => today()->toDateString(),
            'quantity_produced' => 250,
            'quantity_wasted' => 25,
            'notes' => 'Authorized notes-only correction.',
        ]);

        $production = $fixture['production']->refresh();

        self::assertSame($fixture['menuItem']->getKey(), $production->menu_item_id);
        self::assertSame(today()->subDays(2)->toDateString(), $production->production_date->toDateString());
        self::assertSame('10.000', $production->quantity_produced);
        self::assertSame('1.000', $production->quantity_wasted);
        self::assertSame('Authorized notes-only correction.', $production->notes);
        self::assertSame('8.000', $fixture['ingredient']->refresh()->current_stock);
        self::assertSame(1, KitchenStockMovement::query()->whereMorphedTo('reference', $production)->count());
    }

    /**
     * @return array{production: KitchenProduction, menuItem: MenuItem, ingredient: Ingredient}
     */
    private function productionBatch(): array
    {
        $ingredient = Ingredient::query()->create([
            'restaurant_id' => Restaurant::query()->create([
                'name' => 'Audit Restaurant',
                'description' => 'Kitchen production edit-protection test restaurant.',
                'opening_time' => '08:00',
                'closing_time' => '22:00',
            ])->getKey(),
            'name' => 'Audit Rice',
            'unit' => 'kg',
            'current_stock' => 10,
            'reorder_level' => 2,
            'unit_cost' => 5,
            'is_active' => true,
        ]);
        $menuItem = $this->menuItem('Audit Jollof Rice');
        $production = KitchenProduction::query()->create([
            'menu_item_id' => $menuItem->getKey(),
            'production_date' => today()->subDays(2),
            'quantity_produced' => 10,
            'quantity_wasted' => 1,
            'produced_by' => $this->kitchenManager->getKey(),
            'notes' => 'Original production note.',
        ]);
        $production->ingredients()->create([
            'ingredient_id' => $ingredient->getKey(),
            'quantity_used' => 2,
            'unit' => 'kg',
        ]);

        app(KitchenStockService::class)->consumeForProduction($production);

        return compact('production', 'menuItem', 'ingredient');
    }

    private function menuItem(string $name): MenuItem
    {
        $category = MenuCategory::query()->create([
            'name' => "{$name} Category",
            'slug' => str($name)->slug().'-'.str()->random(6),
        ]);

        return MenuItem::query()->create([
            'menu_category_id' => $category->getKey(),
            'name' => $name,
            'slug' => str($name)->slug().'-'.str()->random(6),
            'price' => 25,
            'tracks_kitchen_production' => true,
            'production_unit' => 'portion',
            'inventory_consumption_mode' => 'production_batch',
        ]);
    }
}
