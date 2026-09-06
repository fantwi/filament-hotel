<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\KitchenStockMovements\KitchenStockMovementResource;
use App\Models\Ingredient;
use App\Models\KitchenStockMovement;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;
use Tests\TestCase;

class KitchenStockMovementDetailsTest extends TestCase
{
    public function test_details_schema_exposes_the_complete_read_only_ledger_entry(): void
    {
        $livewire = new class extends Component implements HasSchemas
        {
            use InteractsWithSchemas;
        };
        $schema = KitchenStockMovementResource::infolist(Schema::make($livewire));

        self::assertSame(['default' => 1, 'lg' => 3], $schema->getColumns());
        self::assertSame(
            ['Movement overview', 'Cost and source', 'Notes and audit'],
            array_map(
                fn (Section $section): string => $section->getHeading(),
                $schema->getComponents(),
            ),
        );

        foreach ([
            'ingredient.name',
            'type',
            'direction',
            'quantity_display',
            'balance_change_display',
            'occurred_at',
            'performedBy.name',
            'unit_cost',
            'total_cost',
            'supplier_name',
            'reference_number',
            'source_type',
            'source_reference',
            'notes',
            'created_at',
            'updated_at',
        ] as $componentName) {
            self::assertInstanceOf(
                TextEntry::class,
                $schema->getComponent($componentName),
                "Missing [{$componentName}] from Kitchen Stock Movement details.",
            );
        }

        self::assertNull($schema->getComponent('notes')->getCharacterLimit());
    }

    public function test_details_quantities_use_the_ingredient_unit_and_meaningful_precision(): void
    {
        $movement = new KitchenStockMovement([
            'quantity' => 10,
            'balance_before' => 47.5,
            'balance_after' => 57.125,
        ]);
        $movement->setRelation('ingredient', new Ingredient([
            'name' => 'Rice',
            'unit' => 'kg',
        ]));
        $livewire = new class extends Component implements HasSchemas
        {
            use InteractsWithSchemas;
        };
        $schema = KitchenStockMovementResource::infolist(
            Schema::make($livewire)->record($movement),
        );

        $quantity = $schema->getComponent('quantity_display');
        $balance = $schema->getComponent('balance_change_display');

        self::assertSame('10 kg', $quantity->getState());
        self::assertSame('47.5 → 57.125 kg', $balance->getState());
    }

    public function test_details_use_the_same_accessible_movement_badges_as_the_register(): void
    {
        $livewire = new class extends Component implements HasSchemas
        {
            use InteractsWithSchemas;
        };
        $schema = KitchenStockMovementResource::infolist(Schema::make($livewire));
        $direction = $schema->getComponent('direction');
        $type = $schema->getComponent('type');

        self::assertSame('Stock in', $direction->formatState(KitchenStockMovement::DIRECTION_IN));
        self::assertSame('heroicon-o-arrow-down-tray', $direction->getIcon(KitchenStockMovement::DIRECTION_IN));
        self::assertSame('Stock out', $direction->formatState(KitchenStockMovement::DIRECTION_OUT));
        self::assertSame('heroicon-o-arrow-up-tray', $direction->getIcon(KitchenStockMovement::DIRECTION_OUT));
        self::assertSame('success', $type->getColor(KitchenStockMovement::TYPE_RECEIPT));
        self::assertSame('heroicon-o-arrow-down-tray', $type->getIcon(KitchenStockMovement::TYPE_RECEIPT));
        self::assertSame('danger', $type->getColor(KitchenStockMovement::TYPE_CONSUMPTION));
        self::assertSame('heroicon-o-arrow-up-tray', $type->getIcon(KitchenStockMovement::TYPE_CONSUMPTION));
    }
}
