<?php

namespace App\Filament\Admin\Resources\KitchenProductions\Pages;

use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Configures Filament administration for edit kitchen production.
 */
class EditKitchenProduction extends EditRecord
{
    protected static string $resource = KitchenProductionResource::class;

    /**
     * Saved production batches are inventory ledger records. Restrict updates to
     * notes so a crafted Livewire request cannot desynchronise posted stock.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update([
            'notes' => $data['notes'] ?? null,
        ]);

        return $record;
    }

    /**
     * Describes the only change that the edit workflow persists.
     */
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Production notes updated';
    }
}
