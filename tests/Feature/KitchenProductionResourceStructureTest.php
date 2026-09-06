<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use App\Filament\Admin\Resources\KitchenProductions\Schemas\KitchenProductionForm;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
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
}
