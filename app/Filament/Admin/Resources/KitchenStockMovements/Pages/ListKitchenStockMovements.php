<?php

namespace App\Filament\Admin\Resources\KitchenStockMovements\Pages;

use App\Filament\Admin\Resources\KitchenStockMovements\KitchenStockMovementResource;
use App\Filament\Admin\Widgets\KitchenStockMovementStats;
use App\Models\KitchenProduction;
use App\Models\KitchenStockMovement;
use App\Models\RestaurantOrder;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Configures Filament administration for list kitchen stock movements.
 */
class ListKitchenStockMovements extends ListRecords
{
    protected static string $resource = KitchenStockMovementResource::class;

    /**
     * Explains why this operational ledger intentionally has no edit or delete actions.
     */
    public function getSubheading(): string
    {
        return 'Immutable audit ledger: stock activity cannot be edited or deleted. Correct mistakes with a reversal or an authorized stock adjustment so the full history remains traceable.';
    }

    /**
     * Places filter-aware ledger metrics above the detailed movement table.
     *
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [KitchenStockMovementStats::class];
    }

    /**
     * Keeps the summary row aligned across the full content width.
     */
    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    /**
     * Supplies the header widget with one aggregate of the complete filtered scope.
     *
     * @return array{stockMovementSummary: array<string, float|int|string>}
     */
    public function getWidgetData(): array
    {
        $summary = (clone $this->getFilteredTableQuery())
            ->reorder()
            ->selectRaw(
                'COUNT(*) as movement_count,
                COUNT(DISTINCT ingredient_id) as ingredient_count,
                SUM(CASE WHEN direction = ? THEN 1 ELSE 0 END) as stock_in_count,
                SUM(CASE WHEN direction = ? THEN 1 ELSE 0 END) as stock_out_count,
                COALESCE(SUM(CASE WHEN direction = ? THEN total_cost ELSE 0 END), 0) as stock_in_value,
                COALESCE(SUM(CASE WHEN direction = ? THEN total_cost ELSE 0 END), 0) as stock_out_value',
                [
                    KitchenStockMovement::DIRECTION_IN,
                    KitchenStockMovement::DIRECTION_OUT,
                    KitchenStockMovement::DIRECTION_IN,
                    KitchenStockMovement::DIRECTION_OUT,
                ],
            )
            ->first();

        return [
            'stockMovementSummary' => [
                'movement_count' => (int) ($summary?->movement_count ?? 0),
                'ingredient_count' => (int) ($summary?->ingredient_count ?? 0),
                'stock_in_count' => (int) ($summary?->stock_in_count ?? 0),
                'stock_out_count' => (int) ($summary?->stock_out_count ?? 0),
                'stock_in_value' => (float) ($summary?->stock_in_value ?? 0),
                'stock_out_value' => (float) ($summary?->stock_out_value ?? 0),
                'period_label' => $this->activePeriodLabel(),
            ],
        ];
    }

    /**
     * Adds a guarded export for the same search and filters shown in the ledger.
     *
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->can('export kitchen stock movements') ?? false)
                ->action(fn (): StreamedResponse => $this->exportCsv()),
        ];
    }

    /**
     * Streams all rows in the active table scope without loading the ledger into memory.
     */
    public function exportCsv(): StreamedResponse
    {
        abort_unless(auth()->user()?->can('export kitchen stock movements'), 403);

        $query = (clone $this->getFilteredTableQuery())
            ->with(['ingredient.restaurant', 'performedBy'])
            ->reorder();
        $filename = 'kitchen-stock-movements-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(
            function () use ($query): void {
                $stream = fopen('php://output', 'w');

                if ($stream === false) {
                    return;
                }

                fputcsv($stream, [
                    'Occurred At',
                    'Restaurant',
                    'Ingredient',
                    'Unit',
                    'Movement Type',
                    'Direction',
                    'Quantity',
                    'Balance Before',
                    'Balance After',
                    'Unit Cost (GHS)',
                    'Total Cost (GHS)',
                    'Reference Number',
                    'Source',
                    'Supplier',
                    'Recorded By',
                    'Notes',
                ]);

                $query->lazyById(500)->each(function (KitchenStockMovement $movement) use ($stream): void {
                    fputcsv($stream, array_map($this->csvCell(...), [
                        $movement->occurred_at?->format('Y-m-d H:i:s') ?? '',
                        $movement->ingredient?->restaurant?->name ?? '',
                        $movement->ingredient?->name ?? 'Ingredient unavailable',
                        $movement->ingredient?->unit ?? '',
                        str($movement->type)->replace('_', ' ')->title()->toString(),
                        $movement->direction === KitchenStockMovement::DIRECTION_IN ? 'Stock in' : 'Stock out',
                        number_format((float) $movement->quantity, 3, '.', ''),
                        number_format((float) $movement->balance_before, 3, '.', ''),
                        number_format((float) $movement->balance_after, 3, '.', ''),
                        $movement->unit_cost === null ? '' : number_format((float) $movement->unit_cost, 2, '.', ''),
                        $movement->total_cost === null ? '' : number_format((float) $movement->total_cost, 2, '.', ''),
                        $movement->reference_number ?? '',
                        $this->sourceLabel($movement),
                        $movement->supplier_name ?? '',
                        $movement->performedBy?->name ?? 'System',
                        $movement->notes ?? '',
                    ]));
                });

                fclose($stream);
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    /**
     * Rejects reversed deferred date ranges before applying table filters.
     */
    public function applyTableFilters(): void
    {
        $this->validate(
            [
                'tableDeferredFilters.occurred_at.from' => ['nullable', 'date_format:Y-m-d'],
                'tableDeferredFilters.occurred_at.until' => [
                    'nullable',
                    'date_format:Y-m-d',
                    'after_or_equal:tableDeferredFilters.occurred_at.from',
                ],
            ],
            [
                'tableDeferredFilters.occurred_at.until.after_or_equal' => 'The end date must be on or after the start date.',
            ],
        );

        parent::applyTableFilters();
    }

    /**
     * Converts the active date controls into a concise metric-card label.
     */
    private function activePeriodLabel(): string
    {
        $preset = data_get($this->tableFilters, 'date_preset.value');
        $presetLabel = match ($preset) {
            'today' => 'Today',
            'last_7_days' => 'Last 7 days',
            'this_month' => 'This month',
            default => null,
        };

        if ($presetLabel !== null) {
            return $presetLabel;
        }

        $from = data_get($this->tableFilters, 'occurred_at.from');
        $until = data_get($this->tableFilters, 'occurred_at.until');

        return match (true) {
            filled($from) && filled($until) => "{$from} to {$until}",
            filled($from) => "From {$from}",
            filled($until) => "Through {$until}",
            default => 'All recorded dates',
        };
    }

    /**
     * Describes the model that caused a stock movement.
     */
    private function sourceLabel(KitchenStockMovement $movement): string
    {
        $label = match ($movement->reference_type) {
            null, '' => 'Manual entry',
            (new KitchenProduction)->getMorphClass() => 'Production batch',
            (new RestaurantOrder)->getMorphClass() => 'Food order',
            default => str(class_basename($movement->reference_type))->headline()->toString(),
        };

        return $movement->reference_id === null ? $label : $label.' #'.$movement->reference_id;
    }

    /**
     * Prevents user-entered text from becoming a spreadsheet formula after export.
     */
    private function csvCell(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^\s*[=+\-@]/u', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }
}
