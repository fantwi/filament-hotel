<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use App\Models\KitchenProduction;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Tests\TestCase;

class KitchenProductionTableReadabilityTest extends TestCase
{
    public function test_register_replaces_repeated_quantities_with_responsive_summary_columns(): void
    {
        $table = $this->table();
        $mobileSummary = $table->getColumn('mobile_summary');
        $yieldSummary = $table->getColumn('yield_summary');

        self::assertInstanceOf(TextColumn::class, $mobileSummary);
        self::assertSame('md', $mobileSummary->getHiddenFrom());
        self::assertTrue($mobileSummary->canWrap());

        self::assertInstanceOf(TextColumn::class, $yieldSummary);
        self::assertTrue($yieldSummary->isBadge());
        self::assertTrue($yieldSummary->isListWithLineBreaks());
        self::assertTrue($table->isStackedOnMobile());
        self::assertSame(RecordActionsPosition::BeforeColumns, $table->getRecordActionsPosition());

        foreach (['menuItem.name', 'restaurant.name', 'batch_reference', 'production_date'] as $columnName) {
            self::assertSame(
                'md',
                $table->getColumn($columnName)?->getVisibleFrom(),
                "[{$columnName}] should collapse into the mobile summary below the medium breakpoint.",
            );
        }

        self::assertNull($table->getColumn('quantity_produced'));
        self::assertNull($table->getColumn('quantity_wasted'));
        self::assertNull($table->getColumn('inventory_status')?->getVisibleFrom());
    }

    public function test_synthetic_summary_relationships_are_eager_loaded_independently_of_column_toggles(): void
    {
        $query = $this->table()->applyQueryScopes(KitchenProduction::query());

        self::assertSame(
            ['menuItem', 'restaurant'],
            array_keys($query->getEagerLoads()),
        );
    }

    public function test_register_keeps_view_visible_and_collapses_secondary_actions_into_a_compact_menu(): void
    {
        $actions = array_values($this->table()->getRecordActions());

        self::assertCount(2, $actions);
        self::assertInstanceOf(ViewAction::class, $actions[0]);
        self::assertInstanceOf(ActionGroup::class, $actions[1]);
        self::assertTrue($actions[1]->isIconButton());
        self::assertSame('More actions', $actions[1]->getLabel());
        self::assertSame(['edit', 'void'], array_keys($actions[1]->getFlatActions()));
    }

    public function test_mobile_summary_keeps_batch_identity_readable_without_repeating_yield_values(): void
    {
        $production = $this->production();
        $column = $this->table()->getColumn('mobile_summary');

        self::assertInstanceOf(TextColumn::class, $column);

        $column->record($production)->clearCachedState();

        self::assertSame('Jollof Rice', $column->getState());
        self::assertSame(
            'KP-20260905-ABC123 · Main Restaurant · Sep 05, 2026',
            $column->getDescriptionBelow(),
        );
    }

    public function test_yield_summary_displays_units_net_yield_waste_rate_and_semantic_colors(): void
    {
        $production = $this->production(produced: 80, wasted: 5, unit: 'portion');
        $column = $this->table()->getColumn('yield_summary');

        self::assertInstanceOf(TextColumn::class, $column);

        $column->record($production)->clearCachedState();

        $states = $column->getState();

        self::assertSame([
            'Produced 80.000 portions',
            'Net 75.000 portions',
            'Waste 5.000 portions (6.25%)',
        ], $states);
        self::assertSame(['info', 'success', 'warning'], array_map(
            fn (string $state): string|array|null => $column->getColor($state),
            $states,
        ));
    }

    public function test_yield_summary_marks_zero_waste_as_neutral_and_uses_singular_units(): void
    {
        $production = $this->production(produced: 1, wasted: 0, unit: 'tray');
        $column = $this->table()->getColumn('yield_summary');

        self::assertInstanceOf(TextColumn::class, $column);

        $column->record($production)->clearCachedState();

        $states = $column->getState();

        self::assertSame([
            'Produced 1.000 tray',
            'Net 1.000 tray',
            'Waste 0.000 trays (0.00%)',
        ], $states);
        self::assertSame('gray', $column->getColor($states[2]));
    }

    public function test_yield_summary_flags_invalid_legacy_waste_without_showing_negative_yield(): void
    {
        $production = $this->production(produced: 10, wasted: 12, unit: 'piece');
        $column = $this->table()->getColumn('yield_summary');

        self::assertInstanceOf(TextColumn::class, $column);

        $column->record($production)->clearCachedState();

        $states = $column->getState();

        self::assertSame([
            'Produced 10.000 pieces',
            'Net Invalid waste quantity',
            'Waste 12.000 pieces (Check quantity)',
        ], $states);
        self::assertSame(['info', 'danger', 'danger'], array_map(
            fn (string $state): string|array|null => $column->getColor($state),
            $states,
        ));
    }

    public function test_yield_summary_flags_negative_legacy_waste_instead_of_inflating_net_yield(): void
    {
        $production = $this->production(produced: 10, wasted: -1, unit: 'piece');
        $column = $this->table()->getColumn('yield_summary');

        self::assertInstanceOf(TextColumn::class, $column);

        $column->record($production)->clearCachedState();

        $states = $column->getState();

        self::assertSame([
            'Produced 10.000 pieces',
            'Net Invalid waste quantity',
            'Waste -1.000 piece (Check quantity)',
        ], $states);
        self::assertSame(['info', 'danger', 'danger'], array_map(
            fn (string $state): string|array|null => $column->getColor($state),
            $states,
        ));
    }

    private function table(): Table
    {
        return KitchenProductionResource::table(Table::make($this->createMock(HasTable::class)));
    }

    private function production(float $produced = 80, float $wasted = 5, string $unit = 'portion'): KitchenProduction
    {
        $menuItem = new MenuItem([
            'name' => 'Jollof Rice',
            'production_unit' => $unit,
        ]);
        $restaurant = new Restaurant(['name' => 'Main Restaurant']);
        $production = new KitchenProduction([
            'batch_reference' => 'KP-20260905-ABC123',
            'production_date' => '2026-09-05',
            'quantity_produced' => $produced,
            'quantity_wasted' => $wasted,
        ]);

        $production->setRelation('menuItem', $menuItem);
        $production->setRelation('restaurant', $restaurant);

        return $production;
    }
}
