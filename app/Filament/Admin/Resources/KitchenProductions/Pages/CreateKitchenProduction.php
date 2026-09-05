<?php

namespace App\Filament\Admin\Resources\KitchenProductions\Pages;

use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use App\Models\Ingredient;
use App\Models\KitchenProduction;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Services\KitchenStockService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

            $restaurantId = $data['restaurant_id'] ?? null;

            if (blank($restaurantId) || ! Restaurant::query()->whereKey($restaurantId)->exists()) {
                throw ValidationException::withMessages([
                    'restaurant_id' => 'Select the restaurant preparing this production batch.',
                ]);
            }

            $menuItem = MenuItem::query()->findOrFail($data['menu_item_id']);

            if ($menuItem->inventory_consumption_mode !== 'production_batch') {
                $ingredients = [];
            } elseif ($ingredients === []) {
                throw ValidationException::withMessages([
                    'ingredients' => 'Record at least one ingredient consumed for a production-batch item.',
                ]);
            }

            $ingredientUnits = Ingredient::query()
                ->whereKey(collect($ingredients)->pluck('ingredient_id')->filter()->unique())
                ->where('restaurant_id', $restaurantId)
                ->where('is_active', true)
                ->pluck('unit', 'id');

            if ($ingredientUnits->count() !== collect($ingredients)->pluck('ingredient_id')->filter()->unique()->count()) {
                throw ValidationException::withMessages([
                    'ingredients' => 'Every ingredient must belong to the selected restaurant.',
                ]);
            }

            $production = KitchenProduction::create($data);

            $production->ingredients()->createMany(array_map(
                fn (array $ingredient): array => [
                    'ingredient_id' => $ingredient['ingredient_id'],
                    'quantity_used' => $ingredient['quantity_used'],
                    'unit' => $ingredientUnits->get((int) $ingredient['ingredient_id']),
                    'notes' => $ingredient['notes'] ?? null,
                ],
                $ingredients,
            ));

            app(KitchenStockService::class)->consumeForProduction($production);

            return $production;
        });
    }
}
