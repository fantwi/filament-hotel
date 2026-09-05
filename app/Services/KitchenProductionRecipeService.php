<?php

namespace App\Services;

use App\Models\MenuItem;
use Illuminate\Validation\ValidationException;

/**
 * Builds restaurant-scoped ingredient estimates for kitchen production batches.
 */
class KitchenProductionRecipeService
{
    private const MAX_LEDGER_QUANTITY = 999999999.999;

    /**
     * Scale a production-batch recipe without treating the estimate as actual usage.
     *
     * @return array<int, array{ingredient_id: int, quantity_used: float, notes: ?string}>
     */
    public function estimate(int $menuItemId, int $restaurantId, float $productionQuantity): array
    {
        if ($productionQuantity <= 0) {
            throw ValidationException::withMessages([
                'recipe' => 'Enter a production quantity greater than zero before loading the recipe.',
            ]);
        }

        $menuItem = MenuItem::query()
            ->whereKey($menuItemId)
            ->where('inventory_consumption_mode', 'production_batch')
            ->with(['recipeIngredients.ingredient'])
            ->first();

        if (! $menuItem) {
            throw ValidationException::withMessages([
                'recipe' => 'Select a production-batch menu item before loading a recipe.',
            ]);
        }

        if ($menuItem->recipeIngredients->isEmpty()) {
            throw ValidationException::withMessages([
                'recipe' => 'No recipe ingredients are configured for this menu item.',
            ]);
        }

        $unavailable = $menuItem->recipeIngredients
            ->filter(fn ($recipe): bool => ! $recipe->ingredient
                || ! $recipe->ingredient->is_active
                || (int) $recipe->ingredient->restaurant_id !== $restaurantId)
            ->map(fn ($recipe): string => $recipe->ingredient?->name ?? "Ingredient {$recipe->ingredient_id}")
            ->unique()
            ->values();

        if ($unavailable->isNotEmpty()) {
            throw ValidationException::withMessages([
                'recipe' => 'The recipe contains inactive ingredients or stock from another restaurant: '.$unavailable->join(', ').'.',
            ]);
        }

        $estimates = $menuItem->recipeIngredients
            ->sortBy('ingredient_id')
            ->map(fn ($recipe): array => [
                'ingredient_id' => (int) $recipe->ingredient_id,
                'quantity_used' => round((float) $recipe->quantity_per_item * $productionQuantity, 3),
                'notes' => $recipe->notes,
            ])
            ->values();

        $invalidEstimateIngredientIds = $estimates
            ->filter(fn (array $estimate): bool => $estimate['quantity_used'] < 0.001
                || $estimate['quantity_used'] > self::MAX_LEDGER_QUANTITY)
            ->pluck('ingredient_id');

        if ($invalidEstimateIngredientIds->isNotEmpty()) {
            $invalidNames = $menuItem->recipeIngredients
                ->whereIn('ingredient_id', $invalidEstimateIngredientIds)
                ->map(fn ($recipe): string => $recipe->ingredient->name)
                ->join(', ');

            throw ValidationException::withMessages([
                'recipe' => 'The scaled recipe cannot be stored at three-decimal precision for: '.$invalidNames.'. Adjust the recipe or production quantity.',
            ]);
        }

        return $estimates->all();
    }
}
