<?php

namespace App\Filament\Admin\Resources\KitchenProductions\Pages;

use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use App\Models\KitchenProduction;
use App\Services\KitchenStockService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateKitchenProduction extends CreateRecord
{
    protected static string $resource = KitchenProductionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['produced_by'] = auth()->id();

        return $data;
    }

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
