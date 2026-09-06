<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\KitchenStockMovements\KitchenStockMovementResource;
use App\Models\Ingredient;
use App\Models\KitchenStockMovement;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Tests\TestCase;

class KitchenStockMovementTableReadabilityTest extends TestCase
{
    public function test_register_uses_a_compact_stacked_mobile_summary(): void
    {
        $table = $this->table();
        $summary = $table->getColumn('mobile_summary');

        self::assertInstanceOf(TextColumn::class, $summary);
        self::assertSame('md', $summary->getHiddenFrom());
        self::assertTrue($summary->canWrap());
        self::assertTrue($table->isStackedOnMobile());

        foreach ([
            'occurred_at',
            'ingredient.name',
            'type',
            'direction',
            'quantity',
            'balance_before',
            'balance_after',
            'total_cost',
            'reference_number',
            'performedBy.name',
            'notes',
        ] as $columnName) {
            self::assertSame(
                'md',
                $table->getColumn($columnName)?->getVisibleFrom(),
                "[{$columnName}] should collapse into the mobile summary below the medium breakpoint.",
            );
        }
    }

    public function test_mobile_summary_keeps_the_movement_context_readable(): void
    {
        $movement = new KitchenStockMovement([
            'type' => KitchenStockMovement::TYPE_RECEIPT,
            'direction' => KitchenStockMovement::DIRECTION_IN,
            'quantity' => 10,
            'balance_before' => 47.5,
            'balance_after' => 57.5,
            'occurred_at' => '2026-08-28 14:25:00',
        ]);
        $movement->setRelation('ingredient', new Ingredient([
            'name' => 'Rice',
            'unit' => 'kg',
        ]));

        $column = $this->table()->getColumn('mobile_summary');

        self::assertInstanceOf(TextColumn::class, $column);

        $column->record($movement)->clearCachedState();

        self::assertSame('Rice', $column->getState());
        self::assertSame(
            'Aug 28, 2026 2:25 PM · Receipt · 10 kg in · Balance 47.5 → 57.5 kg',
            $column->getDescriptionBelow(),
        );
    }

    public function test_quantities_show_the_ingredient_unit_without_insignificant_zeroes(): void
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
        $table = $this->table();

        foreach ([
            'quantity' => '10 kg',
            'balance_before' => '47.5 kg',
            'balance_after' => '57.125 kg',
        ] as $columnName => $expected) {
            $column = $table->getColumn($columnName);
            $column?->record($movement)->clearCachedState();

            self::assertSame($expected, $column?->getState());
        }
    }

    public function test_secondary_audit_columns_are_hidden_by_default_on_desktop(): void
    {
        $table = $this->table();

        foreach ([
            'balance_before',
            'total_cost',
            'reference_number',
            'performedBy.name',
            'notes',
        ] as $columnName) {
            $column = $table->getColumn($columnName);

            self::assertTrue($column?->isToggleable(), "[{$columnName}] should remain available in the column manager.");
            self::assertTrue($column?->isToggledHiddenByDefault(), "[{$columnName}] should not crowd the default desktop table.");
        }

        foreach ([
            'occurred_at',
            'ingredient.name',
            'type',
            'direction',
            'quantity',
            'balance_after',
        ] as $columnName) {
            self::assertFalse(
                $table->getColumn($columnName)?->isToggledHiddenByDefault(),
                "[{$columnName}] should remain visible in the default desktop table.",
            );
        }
    }

    public function test_register_exposes_a_read_only_view_action_after_the_ledger_columns(): void
    {
        $table = $this->table();
        $actions = array_values($table->getRecordActions());

        self::assertCount(1, $actions);
        self::assertInstanceOf(ViewAction::class, $actions[0]);
        self::assertSame('view', $actions[0]->getName());
        self::assertSame(RecordActionsPosition::AfterColumns, $table->getRecordActionsPosition());
    }

    private function table(): Table
    {
        return KitchenStockMovementResource::table(
            Table::make($this->createMock(HasTable::class)),
        );
    }
}
