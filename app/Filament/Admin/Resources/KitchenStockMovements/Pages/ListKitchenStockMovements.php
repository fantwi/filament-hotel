<?php

namespace App\Filament\Admin\Resources\KitchenStockMovements\Pages;

use App\Filament\Admin\Resources\KitchenStockMovements\KitchenStockMovementResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Configures Filament administration for list kitchen stock movements.
 */
class ListKitchenStockMovements extends ListRecords
{
    protected static string $resource = KitchenStockMovementResource::class;

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
}
