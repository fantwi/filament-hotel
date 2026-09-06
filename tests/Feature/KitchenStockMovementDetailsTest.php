<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\KitchenStockMovements\KitchenStockMovementResource;
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
}
