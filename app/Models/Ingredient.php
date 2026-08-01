<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $fillable = ['restaurant_id', 'name', 'sku', 'category', 'unit', 'current_stock', 'reorder_level', 'unit_cost', 'is_active'];

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:3',
            'reorder_level' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(KitchenStockMovement::class);
    }

    public function getIsLowStockAttribute(): bool
    {
        return (float) $this->current_stock <= (float) $this->reorder_level;
    }

    public function getStockValueAttribute(): float
    {
        return round((float) $this->current_stock * (float) $this->unit_cost, 2);
    }
}
