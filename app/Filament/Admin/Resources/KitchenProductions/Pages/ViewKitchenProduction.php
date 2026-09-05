<?php

namespace App\Filament\Admin\Resources\KitchenProductions\Pages;

use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Displays a read-only kitchen production batch and its inventory history.
 */
class ViewKitchenProduction extends ViewRecord
{
    protected static string $resource = KitchenProductionResource::class;

    /**
     * Loads the complete details graph once so repeatable cards do not issue per-row queries.
     */
    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->loadMissing([
            'menuItem.category',
            'restaurant',
            'producer',
            'voidedBy',
            'ingredients.ingredient',
            'stockMovements.ingredient',
            'stockMovements.performedBy',
        ]);
    }

    /**
     * Provides the notes-only edit workflow to authorized production staff.
     */
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit notes')
                ->visible(fn (): bool => KitchenProductionResource::canEdit($this->getRecord())),
        ];
    }
}
