<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use App\Filament\Admin\Resources\KitchenProductions\Pages\ListKitchenProductions;
use App\Filament\Admin\Resources\KitchenProductions\Pages\ViewKitchenProduction;
use App\Models\Ingredient;
use App\Models\KitchenProduction;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\KitchenStockService;
use Filament\Facades\Filament;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KitchenProductionDetailsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_resource_registers_a_details_page_and_exposes_it_from_the_production_list(): void
    {
        $staff = $this->kitchenStaff();
        $production = $this->productionBatch($staff);

        self::assertArrayHasKey('view', KitchenProductionResource::getPages());

        $page = Livewire::actingAs($staff)
            ->test(ListKitchenProductions::class)
            ->assertTableActionVisible('view', $production);

        self::assertSame(
            KitchenProductionResource::getUrl('view', ['record' => $production]),
            $page->instance()->getTable()->getRecordUrl($production),
        );
    }

    public function test_details_schema_groups_complete_batch_history_in_a_responsive_layout(): void
    {
        $livewire = new class extends Component implements HasSchemas
        {
            use InteractsWithSchemas;
        };
        $schema = KitchenProductionResource::infolist(Schema::make($livewire));

        self::assertSame(['default' => 1, 'lg' => 3], $schema->getColumns());
        self::assertSame(
            ['Batch overview', 'Yield summary', 'Ingredients consumed', 'Stock movement history', 'Notes and audit', 'Corrections'],
            array_map(
                fn (Section $section): string => $section->getHeading(),
                $schema->getComponents(),
            ),
        );

        foreach ([
            'batch_reference',
            'batch_status',
            'menuItem.name',
            'restaurant.name',
            'production_date',
            'quantity_produced_display',
            'quantity_wasted_display',
            'net_yield_display',
            'waste_percentage_display',
            'ingredients',
            'stockMovements',
            'notes',
            'producer.name',
            'created_at',
            'updated_at',
            'correction_status',
            'voided_at',
            'voidedBy.name',
            'void_reason',
        ] as $componentName) {
            self::assertNotNull(
                $schema->getComponent($componentName),
                "Missing [{$componentName}] from Kitchen Production details.",
            );
        }

        self::assertInstanceOf(RepeatableEntry::class, $schema->getComponent('ingredients'));
        self::assertInstanceOf(RepeatableEntry::class, $schema->getComponent('stockMovements'));
    }

    public function test_authorized_staff_can_view_complete_batch_and_stock_history(): void
    {
        $staff = $this->kitchenStaff([
            'first_name' => 'Kojo',
            'last_name' => 'Mensah',
        ]);
        $production = $this->productionBatch($staff, 'production_batch');
        $ingredient = $this->ingredient($production->restaurant, 'Rice', 'kg', 100);
        $production->ingredients()->create([
            'ingredient_id' => $ingredient->id,
            'quantity_used' => 10,
            'unit' => 'kg',
            'notes' => 'Washed before cooking.',
        ]);

        $this->actingAs($staff);
        app(KitchenStockService::class)->consumeForProduction($production);

        $this->get(KitchenProductionResource::getUrl('view', ['record' => $production]))
            ->assertOk()
            ->assertSeeText($production->batch_reference)
            ->assertSeeText('Posted')
            ->assertSeeText('Details Jollof')
            ->assertSeeText('Details Kitchen')
            ->assertSeeText('80.000 portions')
            ->assertSeeText('5.000 portions')
            ->assertSeeText('75.000 portions')
            ->assertSeeText('6.25%')
            ->assertSeeText('Rice')
            ->assertSeeText('10.000 kg')
            ->assertSeeText('Consumption')
            ->assertSeeText('OUT')
            ->assertSeeText('100.000 → 90.000 kg')
            ->assertSeeText('Washed before cooking.')
            ->assertSeeText('Prepared for dinner service.')
            ->assertSeeText('Kojo Mensah')
            ->assertSeeText('No corrections recorded');
    }

    public function test_details_page_displays_void_correction_and_reversal_history(): void
    {
        $staff = $this->kitchenStaff([
            'first_name' => 'Efua',
            'last_name' => 'Boateng',
        ]);
        $production = $this->productionBatch($staff, 'production_batch');
        $ingredient = $this->ingredient($production->restaurant, 'Cooking Oil', 'litre', 20);
        $production->ingredients()->create([
            'ingredient_id' => $ingredient->id,
            'quantity_used' => 2,
            'unit' => 'litre',
        ]);

        $this->actingAs($staff);
        $stock = app(KitchenStockService::class);
        $stock->consumeForProduction($production);
        $stock->voidProduction($production, 'Duplicate dinner batch.', $staff);

        $this->get(KitchenProductionResource::getUrl('view', ['record' => $production]))
            ->assertOk()
            ->assertSeeText('Voided')
            ->assertSeeText('Voided and reversed')
            ->assertSeeText('Consumption')
            ->assertSeeText('Reversal')
            ->assertSeeText('Duplicate dinner batch.')
            ->assertSeeText('Efua Boateng')
            ->assertSeeText('20.000 → 18.000 litre')
            ->assertSeeText('18.000 → 20.000 litre');
    }

    public function test_voided_batch_without_a_reversal_is_not_reported_as_inventory_reversed(): void
    {
        $staff = $this->kitchenStaff();
        $production = $this->productionBatch($staff, 'none');

        $this->actingAs($staff);
        app(KitchenStockService::class)->voidProduction($production, 'No finished food was produced.', $staff);

        $this->get(KitchenProductionResource::getUrl('view', ['record' => $production]))
            ->assertOk()
            ->assertSeeText('Voided — no stock reversal recorded')
            ->assertDontSeeText('Voided and reversed');
    }

    public function test_view_page_eager_loads_the_complete_details_graph(): void
    {
        $staff = $this->kitchenStaff();
        $production = $this->productionBatch($staff, 'production_batch');
        $ingredient = $this->ingredient($production->restaurant, 'Rice', 'kg', 100);
        $production->ingredients()->create([
            'ingredient_id' => $ingredient->id,
            'quantity_used' => 10,
            'unit' => 'kg',
        ]);

        $this->actingAs($staff);
        app(KitchenStockService::class)->consumeForProduction($production);

        $page = new class extends ViewKitchenProduction
        {
            public function resolveForTest(int|string $key): Model
            {
                return $this->resolveRecord($key);
            }
        };
        $resolved = $page->resolveForTest($production->id);

        foreach (['menuItem', 'restaurant', 'producer', 'voidedBy', 'ingredients', 'stockMovements'] as $relation) {
            self::assertTrue($resolved->relationLoaded($relation), "Expected [{$relation}] to be eager loaded.");
        }

        self::assertTrue($resolved->menuItem->relationLoaded('category'));
        self::assertTrue($resolved->ingredients->sole()->relationLoaded('ingredient'));
        self::assertTrue($resolved->stockMovements->sole()->relationLoaded('ingredient'));
        self::assertTrue($resolved->stockMovements->sole()->relationLoaded('performedBy'));
    }

    public function test_movement_history_prefers_the_recorded_production_unit_snapshot(): void
    {
        $staff = $this->kitchenStaff();
        $production = $this->productionBatch($staff, 'production_batch');
        $ingredient = $this->ingredient($production->restaurant, 'Rice', 'kg', 100);
        $production->ingredients()->create([
            'ingredient_id' => $ingredient->id,
            'quantity_used' => 10,
            'unit' => 'kg',
        ]);

        $this->actingAs($staff);
        app(KitchenStockService::class)->consumeForProduction($production);
        $ingredient->update(['unit' => 'gram']);

        $this->get(KitchenProductionResource::getUrl('view', ['record' => $production]))
            ->assertOk()
            ->assertSeeText('10.000 kg')
            ->assertSeeText('100.000 → 90.000 kg')
            ->assertDontSeeText('100.000 → 90.000 gram');
    }

    public function test_legacy_batch_details_use_clear_placeholders_for_missing_optional_history(): void
    {
        $staff = $this->kitchenStaff();
        $production = $this->productionBatch($staff);
        $production->update([
            'restaurant_id' => null,
            'produced_by' => null,
            'notes' => null,
        ]);

        $this->actingAs($staff)
            ->get(KitchenProductionResource::getUrl('view', ['record' => $production]))
            ->assertOk()
            ->assertSeeText('Legacy batch — restaurant not recorded')
            ->assertSeeText('No ingredient consumption was recorded for this batch.')
            ->assertSeeText('No stock movements are linked to this batch.')
            ->assertSeeText('No production notes recorded')
            ->assertSeeText('System or unknown staff member')
            ->assertSeeText('No corrections recorded');
    }

    public function test_staff_without_kitchen_production_permission_cannot_open_batch_details(): void
    {
        $producer = $this->kitchenStaff();
        $production = $this->productionBatch($producer);
        $unrelatedStaff = User::factory()->create(['department' => 'reception']);

        $this->actingAs($unrelatedStaff)
            ->get(KitchenProductionResource::getUrl('view', ['record' => $production]))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function kitchenStaff(array $attributes = []): User
    {
        $staff = User::factory()->create([
            'department' => 'kitchen_manager',
            ...$attributes,
        ]);
        $staff->givePermissionTo(Permission::findOrCreate('manage kitchen production', 'web'));

        return $staff;
    }

    private function productionBatch(User $producer, string $consumptionMode = 'none'): KitchenProduction
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Details Kitchen',
            'description' => 'Kitchen production details tests.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $category = MenuCategory::query()->create([
            'name' => 'Details Meals',
            'slug' => 'details-meals',
        ]);
        $menuItem = MenuItem::query()->create([
            'menu_category_id' => $category->id,
            'name' => 'Details Jollof',
            'slug' => 'details-jollof',
            'price' => 25,
            'tracks_kitchen_production' => true,
            'production_unit' => 'portion',
            'inventory_consumption_mode' => $consumptionMode,
        ]);

        return KitchenProduction::query()->create([
            'restaurant_id' => $restaurant->id,
            'menu_item_id' => $menuItem->id,
            'production_date' => today(),
            'quantity_produced' => 80,
            'quantity_wasted' => 5,
            'produced_by' => $producer->id,
            'notes' => 'Prepared for dinner service.',
        ]);
    }

    private function ingredient(Restaurant $restaurant, string $name, string $unit, float $stock): Ingredient
    {
        return Ingredient::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => $name,
            'unit' => $unit,
            'current_stock' => $stock,
            'reorder_level' => 2,
            'unit_cost' => 5,
            'is_active' => true,
        ]);
    }
}
