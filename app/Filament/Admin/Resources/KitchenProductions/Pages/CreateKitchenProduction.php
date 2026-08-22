<?php

namespace App\Filament\Admin\Resources\KitchenProductions\Pages;

use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use App\Models\KitchenProduction;
use App\Services\KitchenStockService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Configures Filament administration for create kitchen production.
 */
class CreateKitchenProduction extends CreateRecord
{
    protected static string $resource = KitchenProductionResource::class;

    /**
     * Configures mutate form data before create for the Filament administration interface.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['produced_by'] = auth()->id();

        return $data;
    }

    /**
     * Configures handle record creation for the Filament administration interface.
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): KitchenProduction {
            $ingredients = $data['ingredients'] ?? [];
            unset($data['ingredients']);

            $production = KitchenProduction::create($data);

            $production->ingredients()->createMany(array_map(
                fn (array $ingredient): array => [
                    'ingredient_id' => $ingredient['ingredient_id'],
                    'quantity_used' => $ingredient['quantity_used'],
                    'unit' => $ingredient['unit'] ?? null,
                    'notes' => $ingredient['notes'] ?? null,
                ],
                $ingredients,
            ));

            app(KitchenStockService::class)->consumeForProduction($production);

            return $production;
        });
    }
}
