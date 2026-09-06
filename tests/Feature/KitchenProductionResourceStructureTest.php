<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use App\Filament\Admin\Resources\KitchenProductions\Schemas\KitchenProductionForm;
use App\Filament\Admin\Resources\KitchenProductions\Tables\KitchenProductionsTable;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Livewire\Component;
use Tests\TestCase;

class KitchenProductionResourceStructureTest extends TestCase
{
    public function test_resource_delegates_its_form_to_the_dedicated_configuration(): void
    {
        $livewire = new class extends Component implements HasSchemas
        {
            use InteractsWithSchemas;
        };
        $dedicatedSchema = KitchenProductionForm::configure(Schema::make($livewire));
        $resourceSchema = KitchenProductionResource::form(Schema::make($livewire));

        self::assertCount(2, $dedicatedSchema->getComponents());
        self::assertContainsOnlyInstancesOf(Grid::class, $dedicatedSchema->getComponents());
        self::assertSame(
            array_keys($dedicatedSchema->getFlatComponents(withHidden: true)),
            array_keys($resourceSchema->getFlatComponents(withHidden: true)),
        );

        foreach ([
            'restaurant_id',
            'menu_item_id',
            'production_unit_display',
            'production_date',
            'quantity_produced',
            'quantity_wasted',
            'live_yield_summary',
            'recipe_prefill_status',
            'recipe_estimate_actions',
            'ingredients',
            'ingredient_deduction_mode',
            'recorded_ingredients',
            'produced_by',
        ] as $componentKey) {
            self::assertNotNull(
                $resourceSchema->getComponent($componentKey, withHidden: true),
                "Expected delegated form component [{$componentKey}].",
            );
        }

        $source = file_get_contents(
            app_path('Filament/Admin/Resources/KitchenProductions/KitchenProductionResource.php'),
        );

        self::assertStringContainsString(
            'return KitchenProductionForm::configure($schema);',
            $source,
        );
    }

    public function test_resource_delegates_its_table_to_the_dedicated_configuration(): void
    {
        $dedicatedTable = KitchenProductionsTable::configure(
            Table::make($this->createMock(HasTable::class)),
        );
        $resourceTable = KitchenProductionResource::table(
            Table::make($this->createMock(HasTable::class)),
        );

        self::assertSame(
            array_keys($dedicatedTable->getColumns()),
            array_keys($resourceTable->getColumns()),
        );
        self::assertSame(
            array_keys($dedicatedTable->getFilters()),
            array_keys($resourceTable->getFilters()),
        );

        foreach ([
            'mobile_summary',
            'menuItem.name',
            'restaurant.name',
            'batch_reference',
            'production_date',
            'yield_summary',
            'producer.name',
            'inventory_status',
        ] as $columnName) {
            self::assertNotNull($resourceTable->getColumn($columnName));
        }

        foreach ([
            'restaurant_id',
            'menu_item',
            'category',
            'produced_by',
            'inventory_status',
            'waste_only',
            'date_preset',
            'production_date',
        ] as $filterName) {
            self::assertNotNull($resourceTable->getFilter($filterName));
        }

        self::assertTrue($resourceTable->isStackedOnMobile());
        self::assertSame(
            RecordActionsPosition::BeforeColumns,
            $resourceTable->getRecordActionsPosition(),
        );
    }

    public function test_resource_no_longer_owns_extracted_components_or_obsolete_schema(): void
    {
        $source = file_get_contents(
            app_path('Filament/Admin/Resources/KitchenProductions/KitchenProductionResource.php'),
        );

        self::assertStringContainsString(
            'return KitchenProductionsTable::configure($table);',
            $source,
        );
        self::assertStringNotContainsString('// Section::make', $source);
        self::assertDoesNotMatchRegularExpression(
            '/^use (?:'
                .'App\\\\Models\\\\(?:Ingredient|MenuCategory|MenuItem|Restaurant|User)'
                .'|App\\\\Services\\\\(?:KitchenProductionRecipeService|KitchenStockService)'
                .'|Carbon\\\\CarbonImmutable'
                .'|Closure'
                .'|Filament\\\\(?:Actions|Forms|Infolists|Notifications)'
                .'|Filament\\\\Schemas\\\\Components'
                .'|Filament\\\\Tables\\\\(?:Columns|Enums|Filters)'
                .'|Illuminate\\\\Database\\\\Eloquent\\\\Builder'
                .'|Illuminate\\\\Support\\\\Str'
                .'|Illuminate\\\\Validation\\\\ValidationException'
                .');/m',
            $source,
        );
    }
}
