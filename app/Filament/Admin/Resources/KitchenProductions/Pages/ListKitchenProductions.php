<?php

namespace App\Filament\Admin\Resources\KitchenProductions\Pages;

use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Configures Filament administration for list kitchen productions.
 */
class ListKitchenProductions extends ListRecords
{
    protected static string $resource = KitchenProductionResource::class;

    /**
     * Discards malformed or reversed date filters supplied through a URL.
     */
    public function mount(): void
    {
        parent::mount();

        $range = data_get($this->tableFilters, 'production_date');

        if (! is_array($range)) {
            return;
        }

        $validator = Validator::make($range, [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'until' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $from = $range['from'] ?? null;
        $until = $range['until'] ?? null;

        if ($validator->fails() || (filled($from) && filled($until) && $from > $until)) {
            data_set($this->tableFilters, 'production_date.from', null);
            data_set($this->tableFilters, 'production_date.until', null);
        }
    }

    /**
     * Validates the custom production range before making deferred filters active.
     */
    public function applyTableFilters(): void
    {
        $this->validate([
            'tableDeferredFilters.production_date.from' => ['nullable', 'date_format:Y-m-d'],
            'tableDeferredFilters.production_date.until' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $from = data_get($this->tableDeferredFilters, 'production_date.from');
        $until = data_get($this->tableDeferredFilters, 'production_date.until');

        if (filled($from) && filled($until) && $from > $until) {
            throw ValidationException::withMessages([
                'tableDeferredFilters.production_date.from' => 'The start date must be before or equal to the end date.',
            ]);
        }

        parent::applyTableFilters();
    }

    /**
     * Builds and returns header actions.
     */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
