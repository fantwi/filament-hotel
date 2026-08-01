<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\KitchenStockMovement;
use App\Models\RestaurantOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KitchenStockService
{
    public function receive(Ingredient $ingredient, float $quantity, ?float $unitCost = null, ?string $referenceNumber = null, ?string $notes = null): KitchenStockMovement
    {
        $this->ensurePositiveQuantity($quantity);

        return DB::transaction(function () use ($ingredient, $quantity, $unitCost, $referenceNumber, $notes): KitchenStockMovement {
            $ingredient = $this->lockIngredient($ingredient->id);
            $before = (float) $ingredient->current_stock;
            $after = round($before + $quantity, 3);
            $cost = $unitCost === null
                ? (float) $ingredient->unit_cost
                : round((($before * (float) $ingredient->unit_cost) + ($quantity * $unitCost)) / $after, 2);

            $ingredient->update(['current_stock' => $after, 'unit_cost' => $cost]);

            return $this->movement($ingredient, KitchenStockMovement::TYPE_RECEIPT, KitchenStockMovement::DIRECTION_IN, $quantity, $before, $after, $unitCost ?? $cost, $referenceNumber, notes: $notes);
        });
    }

    public function recordWastage(Ingredient $ingredient, float $quantity, ?string $notes = null): KitchenStockMovement
    {
        $this->ensurePositiveQuantity($quantity);

        return DB::transaction(fn (): KitchenStockMovement => $this->remove($this->lockIngredient($ingredient->id), $quantity, KitchenStockMovement::TYPE_WASTAGE, notes: $notes));
    }

    public function adjustToCountedStock(Ingredient $ingredient, float $countedQuantity, ?string $notes = null): ?KitchenStockMovement
    {
        if ($countedQuantity < 0) {
            throw ValidationException::withMessages(['counted_quantity' => 'Counted stock cannot be negative.']);
        }

        return DB::transaction(function () use ($ingredient, $countedQuantity, $notes): ?KitchenStockMovement {
            $ingredient = $this->lockIngredient($ingredient->id);
            $before = (float) $ingredient->current_stock;
            $difference = round($countedQuantity - $before, 3);

            if ($difference === 0.0) {
                return null;
            }

            if ($difference < 0) {
                return $this->remove($ingredient, abs($difference), KitchenStockMovement::TYPE_ADJUSTMENT_OUT, notes: $notes);
            }

            $ingredient->update(['current_stock' => $countedQuantity]);

            return $this->movement($ingredient, KitchenStockMovement::TYPE_ADJUSTMENT_IN, KitchenStockMovement::DIRECTION_IN, $difference, $before, $countedQuantity, (float) $ingredient->unit_cost, notes: $notes);
        });
    }

    public function consumeForOrder(RestaurantOrder $order): void
    {
        DB::transaction(function () use ($order): void {
            $order = RestaurantOrder::query()->with('items')->lockForUpdate()->findOrFail($order->id);

            if ($order->stock_deducted_at) {
                return;
            }

            $requirements = $this->requirementsFor($order);
            $ingredients = Ingredient::query()->whereIn('id', array_keys($requirements))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $shortages = [];

            foreach ($requirements as $ingredientId => $quantity) {
                $ingredient = $ingredients->get($ingredientId);
                if (! $ingredient) {
                    $shortages[] = "Ingredient {$ingredientId} was not found.";
                } elseif (! $ingredient->is_active) {
                    $shortages[] = "{$ingredient->name} is inactive.";
                } elseif ((float) $ingredient->current_stock < $quantity) {
                    $shortages[] = sprintf('%s requires %.3f %s, but only %.3f is available.', $ingredient->name, $quantity, $ingredient->unit, (float) $ingredient->current_stock);
                }
            }

            if ($shortages !== []) {
                throw ValidationException::withMessages(['stock' => implode(' ', $shortages)]);
            }

            foreach ($requirements as $ingredientId => $quantity) {
                $this->remove($ingredients->get($ingredientId), $quantity, KitchenStockMovement::TYPE_CONSUMPTION, $order, "Ingredient consumption for order {$order->order_number}.");
            }

            $order->update(['stock_deducted_at' => now(), 'stock_reversed_at' => null]);
        });
    }

    public function reverseForOrder(RestaurantOrder $order): void
    {
        DB::transaction(function () use ($order): void {
            $order = RestaurantOrder::query()->lockForUpdate()->findOrFail($order->id);
            if (! $order->stock_deducted_at || $order->stock_reversed_at) {
                return;
            }

            $quantities = KitchenStockMovement::query()
                ->whereMorphedTo('reference', $order)
                ->where('type', KitchenStockMovement::TYPE_CONSUMPTION)
                ->where('direction', KitchenStockMovement::DIRECTION_OUT)
                ->selectRaw('ingredient_id, sum(quantity) as quantity')
                ->groupBy('ingredient_id')
                ->orderBy('ingredient_id')
                ->pluck('quantity', 'ingredient_id');

            $ingredients = Ingredient::query()->whereIn('id', $quantities->keys())->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($quantities as $ingredientId => $quantity) {
                $ingredient = $ingredients->get($ingredientId);
                if (! $ingredient) {
                    continue;
                }
                $before = (float) $ingredient->current_stock;
                $after = round($before + (float) $quantity, 3);
                $ingredient->update(['current_stock' => $after]);
                $this->movement($ingredient, KitchenStockMovement::TYPE_REVERSAL, KitchenStockMovement::DIRECTION_IN, (float) $quantity, $before, $after, (float) $ingredient->unit_cost, $order, "Stock reversal for cancelled order {$order->order_number}.");
            }

            $order->update(['stock_reversed_at' => now()]);
        });
    }

    private function requirementsFor(RestaurantOrder $order): array
    {
        $requirements = [];
        foreach ($order->items as $item) {
            foreach ($item->ingredient_usage_snapshot ?? [] as $line) {
                if (($line['consumption_mode'] ?? 'per_order') !== 'per_order') {
                    continue;
                }
                $id = (int) ($line['ingredient_id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $requirements[$id] = round(($requirements[$id] ?? 0) + ((float) $line['quantity_per_item'] * (int) $item->quantity), 3);
            }
        }
        ksort($requirements);

        return $requirements;
    }

    private function remove(Ingredient $ingredient, float $quantity, string $type, ?Model $reference = null, ?string $notes = null): KitchenStockMovement
    {
        $before = (float) $ingredient->current_stock;
        if ($before < $quantity) {
            throw ValidationException::withMessages(['quantity' => sprintf('%s has only %.3f %s available.', $ingredient->name, $before, $ingredient->unit)]);
        }
        $after = round($before - $quantity, 3);
        $ingredient->update(['current_stock' => $after]);

        return $this->movement($ingredient, $type, KitchenStockMovement::DIRECTION_OUT, $quantity, $before, $after, (float) $ingredient->unit_cost, $reference, $notes);
    }

    private function movement(Ingredient $ingredient, string $type, string $direction, float $quantity, float $before, float $after, ?float $unitCost, Model|string|null $referenceOrNumber = null, ?string $notes = null): KitchenStockMovement
    {
        $reference = $referenceOrNumber instanceof Model ? $referenceOrNumber : null;

        return KitchenStockMovement::create([
            'ingredient_id' => $ingredient->id, 'type' => $type, 'direction' => $direction, 'quantity' => round($quantity, 3),
            'balance_before' => round($before, 3), 'balance_after' => round($after, 3), 'unit_cost' => $unitCost,
            'total_cost' => $unitCost === null ? null : round($quantity * $unitCost, 2),
            'reference_number' => is_string($referenceOrNumber) ? $referenceOrNumber : null,
            'reference_type' => $reference?->getMorphClass(), 'reference_id' => $reference?->getKey(),
            'performed_by' => auth()->id(), 'occurred_at' => now(), 'notes' => $notes,
        ]);
    }

    private function lockIngredient(int $id): Ingredient
    {
        return Ingredient::query()->lockForUpdate()->findOrFail($id);
    }

    private function ensurePositiveQuantity(float $quantity): void
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'The stock quantity must be greater than zero.']);
        }
    }
}
