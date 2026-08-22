<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents ingredient and its persisted business behavior.
 */
class Ingredient extends Model
{
    protected $fillable = ['restaurant_id', 'name', 'sku', 'category', 'unit', 'current_stock', 'reorder_level', 'unit_cost', 'is_active'];

    /**
     * Defines the casts relationship or domain behavior for this model.
     */
    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:3',
            'reorder_level' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Defines the restaurant relationship or domain behavior for this model.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Defines the recipe ingredients relationship or domain behavior for this model.
     */
    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    /**
     * Defines the stock movements relationship or domain behavior for this model.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(KitchenStockMovement::class);
    }

    /**
     * Exposes the computed  is low stock attribute.
     */
    public function getIsLowStockAttribute(): bool
    {
        return (float) $this->current_stock <= (float) $this->reorder_level;
    }

    /**
     * Exposes the computed  stock value attribute.
     */
    public function getStockValueAttribute(): float
    {
        return round((float) $this->current_stock * (float) $this->unit_cost, 2);
    }
}
