<?php
namespace App\Filament\Admin\Resources\KitchenProductions\Pages;
use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use Filament\Resources\Pages\CreateRecord;
class CreateKitchenProduction extends CreateRecord
{
    protected static string $resource = KitchenProductionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['produced_by'] = auth()->id();

        return $data;
    }
}
